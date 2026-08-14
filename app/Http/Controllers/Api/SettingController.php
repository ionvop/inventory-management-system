<?php
// app/Http/Controllers/Api/SettingController.php
namespace App\Http\Controllers\Api;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return $this->data(Setting::pluck('value', 'key'));
    }

    public function update(Request $request)
    {
        foreach ($request->all() as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        return $this->data(Setting::pluck('value', 'key'));
    }
}