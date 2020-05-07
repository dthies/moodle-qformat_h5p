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
 * @package    qformat_glossary
 * @copyright  2020 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace qformat_h5p\local;

use stdClass;
use context_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Question Import for H5P Quiz content type
 *
 * @copyright  2020 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class type_mc extends \qformat_default {

    protected $itemid = 0;

    protected $tempdir;

    protected $template = 'qformat_h5p/questiontext';

    public function __construct($content, $tempdir) {

        $this->metadata = $content->metadata;

        $this->library = $content->library;

        $this->params = $content->params;

        $this->tempdir = $tempdir;
    }

    public function import_question() {
        $qo = $this->import_headers();
        $qo->qtype = 'multichoice';
        $totalcorrect = array_sum(array_column($this->params->answers, 'correct'));
        $qo->single = $totalcorrect == 1;
        // Run through the answers.
        $qo->answer = array();
        $acount = 0;
        foreach ($this->params->answers as $answer) {
            $qo->answer[$acount] = array('text' => $answer->text, 'format' => FORMAT_HTML);
            $qo->fraction[$acount] = round((2.0 * !empty($answer->correct) - 1) / $totalcorrect, 7);
            $qo->feedback[$acount] = array('text' => $answer->tipsAndFeedback->chosenFeedback, 'format' => FORMAT_HTML);
            $acount++;
        }
        return $qo;
    }

    protected function import_media_as_draft($media) {
        global $USER;

        if (empty($media) || empty($media->type->params->file)) {
            return '';
        }
        $fs = get_file_storage();
        $itemid = file_get_unused_draft_itemid();
        $filepaths = array();
        $filename = $media->type->params->contentName;
        $filepath = $this->tempdir . '/content/' . $media->type->params->file->path;
        $fullpath = '/' . $filename;
        $filerecord = array(
            'contextid' => context_user::instance($USER->id)->id,
            'component' => 'user',
            'filearea'  => 'draft',
            'itemid'    => $itemid,
            'filepath'  => '/images/',
            'filename'  => preg_replace('/.*\\//', '', $media->type->params->file->path),
        );
        $fs->create_file_from_pathname($filerecord, $filepath);

        return $itemid;
    }

    /**
     * import parts of question common to all types
     * @param $content array h5p content object
     * @return object question object
     */
    public function import_headers() {
        global $OUTPUT, $USER;

        // This routine initialises the question object.
        $qo = $this->defaultquestion();

        // Question name.
        $qo->name = $this->clean_question_name($this->metadata->title);
        $qo->questiontextformat = FORMAT_HTML;

        $context = new stdClass();
        if (!empty($this->params->media)) {
            $context->media = $this->params->media;
        }
        if (!empty($this->params->question) &&
            !is_object($this->params->question)) {
            $context->questiontext = $this->params->question;
        }

        // Import with media file.
        if (!empty($context->media) && $itemid = $this->import_media_as_draft($context->media)) {
            $qo->questiontextitemid = $itemid;
        }
        $qo->questiontext = $OUTPUT->render_from_template($this->template, $context);

        return $qo;
    }
}
