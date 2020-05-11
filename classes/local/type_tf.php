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
class type_tf extends type_mc {

    public function import_question() {
        $qo = $this->import_headers();
        $qo->qtype = 'truefalse';
        $qo->answer = ($this->params->correct == "true");
        $qo->correctanswer = $qo->answer;
        $qo->feedbacktrue = array(
            'text' => $qo->answer ? $this->params->behaviour->feedbackOnCorrect : $this->params->behaviour->feedbackOnWrong,
            'format' => FORMAT_HTML
        );
        $qo->feedbackfalse = array(
            'text' => $qo->answer ? $this->params->behaviour->feedbackOnWrong : $this->params->behaviour->feedbackOnCorrect,
            'format' => FORMAT_HTML
        );

        return $qo;
    }
}
