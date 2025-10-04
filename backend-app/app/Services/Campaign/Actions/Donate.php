<?php

//
// namespace App\Services\Campaign\Actions;
//
// use App\Models\Campaign\Campaign;
// use App\Models\Campaign\Donation;
// use App\Services\Campaign\DTOs\DonateData;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Str;
//
// class Donate
// {
//    public function handle(DonateData $data): Campaign
//    {
//        return DB::transaction(function () use ($data) {
//            $intent = new Donation;
//            $intent->campaign_id = $data->campaign_id;
//            $intent->requester_email = $data->requester_email;
//            $intent->amount = $data->amount;
//            $intent->currency = $data->currency ?: 'USD';
//            $intent->message = $data->message;
//            $intent->status = 'pending';
//            $intent->token = Str::uuid()->toString();
//            $intent->save();
//
//            return Campaign::query()->findOrFail($data->campaign_id);
//        });
//    }
// }
