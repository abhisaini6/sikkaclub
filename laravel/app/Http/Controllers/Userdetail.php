<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BankDetail;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserDetail extends Controller
{
    public function deposit_withdrawal() {
        $deposit = Transaction::where('userid', user('id'))->orderBy('id', 'desc')->get();
        return view('deposit_withdrawals', compact('deposit'));
    }

    public function profile() {
        $bank = BankDetail::where('userid', user('id'))->first();
        return view('profile', compact('bank'));
    }

    // Api Data Transfer
    public function withdrawal_list() {
        $transaction = Transaction::select('id', 'platform', 'amount', 'remark', 'status', 'created_at')
            ->where('type', 'debit')
            ->where('category', 'withdraw')
            ->get();

        $data = [
            'aaData' => $transaction,
            'iTotalDisplayRecords' => count($transaction),
            'iTotalRecords' => count($transaction),
            'draw' => 'draw'
        ];
        $isSuccess = true;
        $res = ['data' => $data, 'isSuccess' => $isSuccess, 'message' => 'Success'];
        return response()->json($res);
    }

    public function insertwithdrawal(Request $r) {
        $validated = $r->validate([
            'account_no' => 'required',
            'account_holder_name' => 'required',
            'name' => 'required',
            'ifsc_code' => 'required',
            'upi_id' => 'required',
            'mobile_no' => 'required',
            'email' => 'required',
            'address' => 'required'
        ]);

        $withdraw = new Withdrawal;
        $transaction = new Transaction;
        $withdraw->fill($validated);
        if ($withdraw->save()) {
            $transaction->fill([
                'userid' => user('id'),
                'type' => 'withdraw',
                'platform' => 'web',
                'amount' => $r->amount, // Assuming you have an amount field in the request
                'category' => 'withdraw',
                'remark' => 'Withdrawal request',
                'status' => 'pending' // Or set the appropriate status
            ]);
            if ($transaction->save()) {
                return redirect('/dashboard');
            }
        }
        return back()->withErrors(['message' => 'Failed to process withdrawal']);
    }

    public function get_user_detail() {
        $avatar = 'images/avtar/av-1.png';
        $data = ['username' => user('id'), 'avatar' => $avatar, 'notification' => ''];
        $res = ['data' => $data, 'isSuccess' => true, 'message' => 'Success'];
        return response()->json($res);
    }

public function is_login(Request $request)
{
    // Retrieve parameters from query string
   $username = $request->input('username');
   $password = $request->input('password');
    // Log received parameters for debugging
    \Log::info("Received username: {$username}");
    \Log::info("Received password: {$password}");
    \Log::info('Full request URL: ' . $request->fullUrl());
    // Check if username and password are provided
    if (empty($username) || empty($password)) {
        return response()->json(['isSuccess' => false, 'message' => 'Username or password not provided']);
    }

    // Find the user by phone number
    $user = User::where('phone', $username)->first();

    if (!$user) {
        return response()->json(['isSuccess' => false, 'message' => 'User not found']);
    }

    // Hash the provided password
    $hashedPassword = md5($password);
    $storedPassword = $user->password;

    if ($hashedPassword === $storedPassword) {
        // Store user data in session
        $request->session()->put('userlogin', [
            'id' => $user->id,
            // Add other session data as needed
        ]);

        // Redirect to the appropriate URL with the user ID
        return redirect()->to("https://aviator.sikkaclubs.com/crash?id={$user->id}");
    }

    return response()->json(['isSuccess' => false, 'message' => 'Invalid credentials']);
}




    public function wallet_transfer(Request $r) {
        $userid = $r->userid;
        $amount = $r->amount;
        $message = '';
        $isSuccess = false;

        $exist = User::where('id', $userid)->whereNull('isadmin')->first();
        if ($exist) {
            if (wallet(user('id'), 'num') > 0 && wallet(user('id'), 'num') >= $amount) {
                addwallet($userid, $amount);
                addtransaction($userid, 'Transfer By ~' . user('id'), date('ydmhsi'), 'credit', $amount, 'transfer', 'Success', '1');
                addwallet(user('id'), $amount, '-');
                addtransaction(user('id'), 'Transfer To ~' . $userid, date('ydmhsi'), 'debit', $amount, 'transfer', 'Success', '1');
                $message = 'Success';
                $isSuccess = true;
            } else {
                $message = 'Amount not enough!';
            }
        } else {
            $message = 'User ID not found!';
        }

        $res = ['isSuccess' => $isSuccess, 'message' => $message];
        return response()->json($res);
    }
}
