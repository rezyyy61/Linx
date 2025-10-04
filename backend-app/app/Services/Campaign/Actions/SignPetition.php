<?php

//
// namespace App\Services\Campaign\Actions;
//
// use App\Models\Campaign\Campaign;
// use App\Services\Campaign\DTOs\SignData;
// use Illuminate\Support\Carbon;
// use Illuminate\Support\Facades\DB;
//
// class SignPetition
// {
//    public function handle(SignData $data): Campaign
//    {
//        return DB::transaction(function () use ($data) {
//            $exists = DB::table('campaign_signatures')
//                ->where('campaign_id', $data->campaign_id)
//                ->where('email', $data->email)
//                ->exists();
//            if (! $exists) {
//                DB::table('campaign_signatures')->insert([
//                    'campaign_id' => $data->campaign_id,
//                    'name' => $data->name,
//                    'email' => $data->email,
//                    'created_at' => Carbon::now(),
//                    'updated_at' => Carbon::now(),
//                ]);
//            }
//
//            return Campaign::query()->findOrFail($data->campaign_id);
//        });
//    }
// }
