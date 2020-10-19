<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Question Import for H5P Quiz content type
 *
 * @package    qformat_h5p
 * @copyright  2020 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace qformat_h5p\local;

defined('MOODLE_INTERNAL') || die();

require($CFG->libdir . '/licenselib.php');
use stdClass;
use context_user;
use license_manager;

/**
 * Question Import for H5P Quiz content type
 *
 * @copyright  2020 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class type_mc extends \qformat_default {

    /** @var int $itemid questiontext itemid */
    protected $itemid = 0;

    /** @var string $tempdir The temporary directory containing unzipped content type */
    protected $tempdir;

    /** @var string $template The name of template used to format question */
    protected $template = 'qformat_h5p/questiontext';

    /**
     * Constructor
     *
     * @param object $content object from content.json file
     * @param string $tempdir tempoorary directory location
     */
    public function __construct($content, $tempdir) {

        $this->metadata = $content->metadata;

        $this->library = $content->library;

        $this->params = $content->params;

        $this->tempdir = $tempdir;
    }

    /**
     * Converts the content object to question object
     *
     * @return object question data
     */
    public function import_question() {
        $qo = $this->import_headers();
        $qo->qtype = 'multichoice';
        $totalcorrect = array_sum(array_column($this->params->answers, 'correct'));
        $qo->single = ($totalcorrect == 1);
        // Run through the answers.
        $qo->answer = array();
        $acount = 0;
        foreach ($this->params->answers as $answer) {
            $qo->answer[$acount] = array('text' => html_to_text($answer->text), 'format' => FORMAT_HTML);
            if ($qo->single) {
                $qo->fraction[$acount] = !empty($answer->correct);
            } else {
                $qo->fraction[$acount] = round((2.0 * !empty($answer->correct) - 1) / $totalcorrect, 7);
            }
            $qo->feedback[$acount] = array('text' => $answer->tipsAndFeedback->chosenFeedback, 'format' => FORMAT_HTML);
            $acount++;
        }
        return $qo;
    }

    /**
     * Parse any attached media and add to filearea
     *
     * @param object $media object in content
     * @return int the itemid to be used for questiontext filearea
     */
    protected function import_sources_as_draft($media) {
        global $USER;

        if (empty($media)) {
            return '';
        }
        $fs = get_file_storage();
        if (empty($this->itemid)) {
            $this->itemid = file_get_unused_draft_itemid();
        }
        foreach ($media as $source) {
            $filename = preg_replace('/.*\\//', '', $source->path);
            $filepath = $this->tempdir . '/content/' . $source->path;
            $filerecord = array(
                'author'    => implode(', ', array_column(
                    $source->metadata->authors,
                    'name'
                )),
                'contextid' => context_user::instance($USER->id)->id,
                'component' => 'user',
                'filearea'  => 'draft',
                'itemid'    => $this->itemid,
                'filepath'  => preg_replace('/[^\\/]*$/', '', '/' . $source->path),
                'filename'  => $filename,
            );
            if ($license = $this->get_license($source->metadata)) {
                $filerecord['license'] = $license->id;
            }
            $fs->create_file_from_pathname($filerecord, $filepath);
        }

        return $this->itemid;
    }

    /**
     * Parse any attached media and add to filearea
     *
     * @param object $media object in content
     * @return int the itemid to be used for questiontext filearea
     */
    protected function import_media_as_draft($media) {
        global $USER;

        if (empty($media) || empty($media->type->params->file)) {
            return '';
        }
        $fs = get_file_storage();
        $itemid = file_get_unused_draft_itemid();
        $filename = preg_replace('/.*\\//', '', $media->type->params->file->path);
        $filepath = $this->tempdir . '/content/' . $media->type->params->file->path;
        $filerecord = array(
            'author'    => implode(', ', array_column(
                $media->type->metadata->authors,
                'name'
            )),
            'contextid' => context_user::instance($USER->id)->id,
            'component' => 'user',
            'filearea'  => 'draft',
            'itemid'    => $itemid,
            'filepath'  => '/images/',
            'filename'  => $filename,
        );
        if ($license = $this->get_license($media->type->metadata)) {
            $filerecord['license'] = $license->id;
        }
        $fs->create_file_from_pathname($filerecord, $filepath);

        return $itemid;
    }

    /**
     * Import parts of question common to all types
     *
     * @return object question object
     */
    public function import_headers() {
        global $OUTPUT;

        // This routine initialises the question object.
        $qo = $this->defaultquestion();

        // Question name.
        $qo->name = $this->clean_question_name($this->metadata->title);
        $qo->questiontextformat = FORMAT_HTML;

        $context = new stdClass();
        if (!empty($this->params->media)) {
            $context->media = $this->params->media;
        }
        if (!empty($this->params->audio)) {
            $context->audio = $this->params->audio;
            $context->hasaudio = true;
        }
        if (!empty($this->params->question) &&
            !is_object($this->params->question)) {
            $context->questiontext = strip_tags(
                $this->params->question,
                '<div><p><h1><h2><h3><h4><h5><h6><span><strong><b><i><em>'
            );
        }

        // Import with media file.
        if (!empty($context->media)) {
            if (!$this->itemid = $this->import_media_as_draft($context->media)) {
                $this->itemid = $this->import_sources_as_draft($context->media->type->params->sources);
                $context->hasvideo = !empty($context->media->type->params->sources);
            }
        }
        if (!empty($context->audio) && $itemid = $this->import_sources_as_draft($context->audio)) {
            $this->itemid = $itemid;
        }
        if (!empty($this->itemid)) {
            $qo->questiontextitemid = $this->itemid;
        }
        $qo->questiontext = $OUTPUT->render_from_template($this->template, $context);

        foreach ($this->params->overallFeedback as $feedback) {
            if (($feedback->from === 0) && ($feedback->to < 100)) {
                $qo->incorrectfeedback = array(
                    'text' => $feedback->feedback,
                    'format' => FORMAT_HTML,
                );
            }
            if (($feedback->from === 1) && ($feedback->to === 99)) {
                $qo->partiallycorrectfeedback = array(
                    'text' => $feedback->feedback,
                    'format' => FORMAT_HTML,
                );
            }
            if (($feedback->from > 0) && ($feedback->to === 100)) {
                $qo->correctfeedback = array(
                    'text' => $feedback->feedback,
                    'format' => FORMAT_HTML,
                );
            }
        }
        return $qo;
    }

    /*
     * Return standard Moodle license from H5P metadata
     *
     * @param {object) The metadata for content
     *
     * @return (object|null} The license record if found
     *
     */
    public function get_license($metadata) {
        if (empty($metadata) || empty($metadata->license)) {
            return;
        }
        $shortnames = array(
            'C' => 'allrightsreserved',
            'CC BY' => 'cc',
            'CC BY-NC' => 'cc-nc',
            'CC BY-NC-ND' => 'cc-nc-nd',
            'CC BY-NC-SA' => 'cc-nc-sa',
            'CC BY-ND' => 'cc-nd',
            'CC BY-SA' => 'cc-sa',
            'PD' => 'public',
            'U' => 'unknown',
        );
        if (key_exists($metadata->license, $shortnames)) {
            return license_manager::get_license_by_shortname($shortnames[$metadata->license]);
        }
        return license_manager::get_license_by_shortname('unknown');
    }
}
