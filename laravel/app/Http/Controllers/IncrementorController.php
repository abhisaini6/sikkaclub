<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Incrementor;
use Illuminate\Support\Facades\DB;

class IncrementorController extends Controller
{
    public function generateRandomValues()
    {
        $values = [];
        for ($i = 0; $i < 8; $i++) {
            $values[] = $this->generateRandomValue(1.0, 1.85);
        }
        for ($i = 0; $i < 4; $i++) {
            $values[] = $this->generateRandomValue(2.1, 3.0);
        }
        for ($i = 0; $i < 1; $i++) {
            $values[] = $this->generateRandomValue(3.0, 10.0);
        }
        while (count($values) < 20) {
            $values[] = $this->generateRandomValue(1.0, 1.85);
        }
        shuffle($values);
        return $values;
    }

    private function generateRandomValue($min, $max)
    {
        return mt_rand() / mt_getrandmax() * ($max - $min) + $min;
    }

    public function updateIncrementorValues()
    {
        $values = $this->generateRandomValues();
        $incrementors = Incrementor::take(count($values))->get();

        foreach ($incrementors as $index => $incrementor) {
            $incrementor->same = $values[$index];
            $incrementor->save();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Values updated successfully!',
            'values' => $values,
        ]);
    }

    public function insertDummyData()
    {
        $values = $this->generateRandomValues();
        foreach ($values as $value) {
            Incrementor::create(['same' => $value]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Dummy data inserted successfully!',
            'values' => $values,
        ]);
    }
}
