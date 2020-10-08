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

use qformat_h5p\local;

use stdClass;
use context_user;
use question_bank;

defined('MOODLE_INTERNAL') || die();

/**
 * Question Import for H5P Quiz content type
 *
 * @copyright  2020 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class type_dtw extends type_mc {

    /**
     * Converts the content object to question object
     *
     * @return object question data
     */
    public function import_question() {
        // Locate answers and replace.
        preg_match_all('/\\*([^\\*]+)\\*/', $this->params->textField, $answers);

        $this->params->question = $this->params->textField;
        $answercount = count($answers[1]);
        for ($i = 1; $i <= $answercount; $i++) {
            $this->params->question = preg_replace( '/\\*([^\\*]+)\\*/', "[[$i]]", $this->params->question, 1);
        }

        $this->params->question = '<div>'. $this->params->taskDescription . '</div>' .
            preg_replace('/(.+)$/m', '<div>$1</div>', $this->params->question);
        $qo = $this->import_headers();
        $qo->qtype = 'ddwtos';
        $qo->choices = array();
        foreach ($answers[1] as $answer) {
            $qo->choices[] = array(
                'answer' => preg_replace('/(:|\\\\[\\-\\+]).*/', '', $answer),
                'choicegroup' => 1,
                'infinie' => false,
            );
        }
        if (!empty($this->itemid)) {
            $qo->questiontextitemid = $this->itemid;
        }

        return $qo;
    }

}
