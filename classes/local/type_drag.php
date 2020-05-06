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
class type_drag extends type_mc {

    public function import_question() {
        $itemid = $this->import_question_files_as_draft($this->params->question);
        $qo = $this->import_headers();
        $qo->questiontext = '';
        $qo->questiontextformat = FORMAT_HTML;
        $qo->bgimage = $itemid;
        $qo->qtype = 'ddmarker';

        $qo->drags = array();
        foreach ($this->params->question->task->elements as $dragindex => $element) {
            $qo->drags[$dragindex] = array(
                'label' => strip_tags($element->type->params->text),
                'noofdrags' => 1,
            );
        }
        $qo->drops = array();
        $height = 4;
        $width = 6;
        foreach ($this->params->question->task->dropZones as $dropindex => $zone) {
            $qo->drops[] = array(
                'choice' => reset($zone->correctElements) + 1,
                'shape' => 'rectangle',
                'coords' => round($zone->x * $width) . ',' . round($zone->y * $height). ';' . round($zone->width * $width) . ',' .
                round($zone->height * $height),
            );
        }
        return $qo;
    }

    protected function import_question_files_as_draft($question) {
        global $USER;

        $fs = get_file_storage();
        $itemid = file_get_unused_draft_itemid();
        $filepath = $this->tempdir . '/content/' . $question->settings->background->path;
        $filerecord = array(
            'contextid' => context_user::instance($USER->id)->id,
            'component' => 'user',
            'filearea'  => 'draft',
            'itemid'    => $itemid,
            'filepath'  => '/',
            'filename'  => preg_replace('/.*\\//', '', $question->settings->background->path),
        );
        $fs->create_file_from_pathname($filerecord, $filepath);

        return $itemid;
    }
}
