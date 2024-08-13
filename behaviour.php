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
 * Question behaviour for the case when the student's answer is just
 * saved until they submit the whole attempt, and then it is graded
 * with addidtional fields of grading treated as qt data.
 *
 * @package    qbehaviour
 * @subpackage qtdeferredfeedback
 * @copyright  2024 Leon Camus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/behaviour/deferredfeedback/behaviour.php');

/**
 * Question behaviour for deferred feedback.
 *
 * The student enters their response during the attempt, and it is saved. Later,
 * when the whole attempt is finished, their answer is graded
 * with addidtional fields of grading treated as qt data.
 *
 * @copyright  2024 Leon Camus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qbehaviour_qtdeferredfeedback extends qbehaviour_deferredfeedback {
    public function process_finish(question_attempt_pending_step $pendingstep)
    {
        if ($this->qa->get_state()->is_finished()) {
            return question_attempt::DISCARD;
        }

        $response = $this->qa->get_last_step()->get_qt_data();
        if (!$this->question->is_gradable_response($response)) {
            $pendingstep->set_state(question_state::$gaveup);
        } else {
            $gradedata = $this->question->grade_response($response);
            $pendingstep->set_fraction($gradedata[0]);
            $pendingstep->set_state($gradedata[1]);
            unset($gradedata[0], $gradedata[1]);

            if (count($gradedata) > 0) {
                foreach ($gradedata as $name => $value) {
                    $pendingstep->set_qt_var($name, $value);
                }
            }
        }
        $pendingstep->set_new_response_summary($this->question->summarise_response($response));
        return question_attempt::KEEP;
    }
}
