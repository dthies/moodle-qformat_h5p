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

use stdClass;
use context_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Question Import for H5P Quiz content type
 *
 * @copyright  2020 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class type_dnd extends type_mc {

    /**
     * Converts the content object to question object
     *
     * @return object question data
     */
    public function import_question() {
        global $CFG, $USER;
        if (!$itemid = $this->import_question_files_as_draft($this->params->question)) {
            $fs = get_file_storage();
            $itemid = file_get_unused_draft_itemid();
            $filerecord = array(
                'contextid' => context_user::instance($USER->id)->id,
                'component' => 'user',
                'filearea'  => 'draft',
                'itemid'    => $itemid,
                'filepath'  => '/',
                'filename'  => 'background.png',
            );

            $fs->create_file_from_pathname($filerecord, $CFG->dirroot . '/question/format/h5p/defaultbackground.png');
        }
        $qo = $this->import_headers();
        $qo->questiontext = '';
        $qo->questiontextformat = FORMAT_HTML;
        $qo->bgimage = $itemid;
        $qo->qtype = 'ddimageortext';

        $qo->drags = array();
        foreach ($this->params->question->task->elements as $dragindex => $element) {
            $itemid = $this->import_question_files_as_draft($element);
            if (!empty($itemid)) {
                $qo->drags[$dragindex] = array(
                    'dragitemtype' => 'image',
                    'draggroup' => 1,
                    'infinite' => false,
                );
            } else {
                $qo->drags[$dragindex] = array(
                    'dragitemtype' => 'word',
                    'draggroup' => 1,
                    'infinite' => false,
                );
                $qo->draglabel[$dragindex] = 'Drag me';
            }
            $qo->dragitem[$dragindex] = $itemid;
            if (!empty($element->type->params->text)) {
                $qo->draglabel[$dragindex] = strip_tags($element->type->params->text);
            } else if (!empty($element->type->params->alt)) {
                $qo->draglabel[$dragindex] = strip_tags($element->type->params->alt);
            }
        }
        $qo->drops = array();
        $height = 4;
        $width = 6;
        foreach ($this->params->question->task->dropZones as $dropindex => $zone) {
            $qo->drops[] = array(
                'choice' => reset($zone->correctElements) + 1,
                'xleft' => round($zone->x * $width),
                'ytop' => round($zone->y * $height),
                'droplabel' => !empty($zone->showLabel) ? strip_tags($zone->label) : '',
                'coords' => round($zone->x * $width) . ',' . round($zone->y * $height). ';' . round($zone->width * $width) . ',' .
                round($zone->height * $height),
            );
        }
        return $qo;
    }

    /**
     * Parse attached file used as drags or background
     *
     * @param object $question the object containing file params
     * @return int the itemid to be used for filearea
     */
    protected function import_question_files_as_draft($question) {
        global $USER;

        if (!empty($question->settings->background)) {
            $filepath = $this->tempdir . '/content/' . $question->settings->background->path;
        } else if (!empty($question->type->params->file)) {
            $filepath = $this->tempdir . '/content/' . $question->type->params->file->path;
        } else {
            return '';
        }

        $fs = get_file_storage();
        $itemid = file_get_unused_draft_itemid();
        $filerecord = array(
            'contextid' => context_user::instance($USER->id)->id,
            'component' => 'user',
            'filearea'  => 'draft',
            'itemid'    => $itemid,
            'filepath'  => '/',
            'filename'  => preg_replace('/.*\\//', '', $filepath),
        );

        $fs->create_file_from_pathname($filerecord, $filepath);

        return $itemid;
    }
}
