<?php
// app/Http/Controllers/QuizController.php
namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\AdminConfiguration;
use Illuminate\Support\Facades\Auth;
use App\Models\{Question, QuizSession, QuizAnswer, User};

class QuizController extends Controller
{
    // Start quiz: randomize questions & options
    public function start(Request $request)
    {
        $user = Auth::user();
        $adminConf = AdminConfiguration::first();

        // check if a session already exists today
        $session = QuizSession::where('user_id', $user->id)
            ->whereDate('created_at', Carbon::today())
            ->first();

        // if ($session) {
        //     return $this->errRes(null, 'You’ve reached daily limit. Try again tomorrow!');
        // }

        // Create session
        $session = QuizSession::create([
            'user_id' => $user->id,
            'score' => 0,
        ]);

        // Pick 10 random questions
        $questions = Question::inRandomOrder()->limit($adminConf->no_question)->get()->map(function ($q) {
            $options = $q->options;
            shuffle($options); // randomize options
            return [
                'id' => $q->id,
                'question' => $q->question,
                'options' => $options,
                'correct' => $q->correct,
            ];
        });

        $data = [
            'session_id' => $session->id,
            'questions' => $questions,
        ];
        return $this->sucRes(
            $data,
            'Questions'
        );
    }

    // Save answer
    public function answer(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:quiz_sessions,id',
            'question_id' => 'required|exists:questions,id',
            'selected' => 'required|string',
        ]);

        $adminConf = AdminConfiguration::first();

        $session = QuizSession::find($request->session_id);
        $question = Question::find($request->question_id);

        $isCorrect = $request->selected === $question->correct;

        QuizAnswer::updateOrCreate(
            ['quiz_session_id' => $session->id, 'question_id' => $question->id],
            ['selected' => $request->selected, 'is_correct' => $isCorrect]
        );

        if ($isCorrect) {
            $session->score += $adminConf->award_point; // 2 points per correct answer
            $session->no_correct += 1;
            $session->save();
        } else {
            $session->no_wrong += 1;
            $session->save();
        }

        $data = [
            'correct' => $isCorrect,
            'current_score' => $session->score,
        ];
        return $this->sucRes(
            $data,
            'Answer'
        );
    }

    // Boost time: deduct balance
    public function boost(Request $request)
    {
        $user = User::find(Auth::user()->id);
        $adminConf = AdminConfiguration::first();

        if ($user->wallet_balance < $adminConf->boost_time_amount) {
            return $this->errRes(null, 'Insufficient balance');
        }

        $afterBal = $user->wallet_balance -= $adminConf->boost_time_amount;
        Transaction::create([
            'user_id' => Auth::user()->id,
            'type' => 'game',
            'amount' => $adminConf->boost_time_amount,
            'status' => 'completed',
            'ref' => $ref ?? uniqid(),
            'description' => 'Quiz boost',
            'balance_before' => $user->wallet_balance,
            'balance_after' => $afterBal
        ]);

        $user->update(['wallet_balance' => $afterBal]);
        return $this->sucRes(
            $afterBal,
            'Boost purchased, ' . $adminConf->boost_time_amount . ' seconds added'
        );
    }

    // Quiz complete: add point
    public function complete(Request $request)
    {
        $user = User::find(Auth::user()->id);
        $session = QuizSession::find($request->session_id);

        $afterBal = $user->whipple_point += $session->score;
        Transaction::create([
            'user_id' => Auth::user()->id,
            'type' => 'game',
            'amount' => $session->score,
            'status' => 'completed',
            'ref' => $ref ?? uniqid(),
            'description' => 'Quiz wins',
            'point_before' => $user->whipple_point,
            'point_after' => $afterBal
        ]);

        $user->update(['whipple_point' => $afterBal]);
        $data = [
            'balance' => $afterBal,
            'score' => $session->score,
            'correct' => $session->no_correct,
            'wrong' => $session->no_correct
        ];
        return $this->sucRes(
            $data,
            'You won' . $session->score
        );
    }

    // Quiz complete: add point
    public function close(Request $request)
    {
        QuizSession::find($request->session_id)->delete();
        QuizAnswer::where('quiz_session_id', $request->session_id)->delete();

        return $this->sucRes(
            null,
            'You have successfully quit the game'
        );
    }
}
