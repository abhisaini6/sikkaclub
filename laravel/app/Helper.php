<?php

use App\Models\Gameresult;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Userbit;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

function imageupload($file, $name, $path)
{
    $file_name = "";
    $file_type = "";
    $filePath = "";
    $size = "";

    if ($file) {
        $file_name = $file->getClientOriginalName();
        $file_type = $file->getClientOriginalExtension();
        $fileName = $name . "." . $file_type;
        Storage::disk('public')->put($path . $fileName, File::get($file));
        $filePath = "/" . 'storage/' . $path . $fileName;
    }
    return [
        'fileName' => $file_name,
        'fileType' => $file_type,
        'filePath' => $filePath,
    ];
}

function datealgebra($date, $operator, $value, $format = "Y-m-d")
{
    $date = date_create($date);
    if ($operator == "-") {
        date_sub($date, date_interval_create_from_date_string($value));
    } elseif ($operator == "+") {
        date_add($date, date_interval_create_from_date_string($value));
    }
    return date_format($date, $format);
}

function user($parameter, $id = null)
{
    $userlogin = session()->get('userlogin');

    if ($id === null) {
        // Check if userlogin is an array before accessing it
        if (is_array($userlogin)) {
            return $userlogin[$parameter] ?? null;
        } else {
            // Handle the case where userlogin is not an array
            return null;
        }
    } else {
        $data = User::find($id);
        return $data ? $data->{$parameter} : null;
    }
}


function userdetail($id, $parameter)
{
    $data = User::find($id);
    return $data ? $data->{$parameter} : null;
}

function admin($parameter)
{
    return session()->get('adminlogin')[$parameter];
}

function wallet($userid, $type = "string")
{
    $user = User::find($userid);

    if ($user) {
        if (isset($user->money)) {
            return $type === "num" ? $user->money : number_format($user->money);
        }
        return 0;
    }

    return 0;
}




function setting($parameter)
{
    $setting = Setting::where('category', $parameter)->first();
    return $setting ? $setting->value : null;
}

function currentid()
{
    $data = Gameresult::orderBy('id', 'desc')->first();
    return $data ? $data->id : 0;
}

function dformat($date, $format)
{
    $strd = date_create($date);
    return date_format($strd, $format);
}

function resultbyid($id)
{
    $data = Gameresult::find($id);
    return $data && $data->result && $data->result !== 'pending' ? $data->result : 0;
}

function userbetdetail($id, $parameter)
{
    $data = Userbit::find($id);
    return $data ? $data->{$parameter} : 0;
}

function addwallet($id, $amount, $symbol = "+")
{
    $wallet = User::where('id', $id)->first();
    if ($wallet) {
        $currentAmount = wallet($id, 'num');
        if ($symbol == "+") {
            $wallet->money = $currentAmount + $amount;
        } elseif ($symbol == "-") {
            $wallet->money = $currentAmount - $amount;
        }
        $wallet->save();
        return wallet($id, 'num');
    }
    return 0;
}


function appvalidate($input)
{
    return empty($input) || $input == 0 ? 'Not found!' : $input;
}

function lastrecharge($id, $parameter)
{
    $data = Transaction::where('userid', $id)
        ->where('type', 'credit')
        ->where('category', 'recharge')
        ->orderBy('id', 'desc')
        ->first();
    return $data ? $data->{$parameter} : false;
}

function status($code, $type)
{
    $status = [
        'recharge' => [
            0 => ['color' => 'warning', 'name' => 'Pending'],
            1 => ['color' => 'success', 'name' => 'Approved'],
            2 => ['color' => 'danger', 'name' => 'Cancel']
        ],
        'user' => [
            0 => ['color' => 'danger', 'name' => 'Inactive'],
            1 => ['color' => 'success', 'name' => 'Active'],
            2 => ['color' => 'warning', 'name' => 'Pending']
        ]
    ];

    return $status[$type][$code] ?? null;
}

function platform($id)
{
    $platforms = [
        1 => 'gpay',
        2 => 'phonepay',
        3 => 'upi',
        6 => 'netbanking',
        9 => 'imps'
    ];
    return $platforms[$id] ?? 'other';
}

function addtransaction($userid, $platform, $transactionno, $type, $amount, $category, $remark, $status)
{
    $trn = new Transaction;
    $trn->userid = $userid;
    $trn->platform = $platform;
    $trn->transactionno = $transactionno;
    $trn->type = $type;
    $trn->amount = $amount;
    $trn->category = $category;
    $trn->remark = $remark;
    $trn->status = $status;
    return $trn->save();
}
