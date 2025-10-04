<?php

//
// namespace App\Http\Controllers\Api\Campaign;
//
// use App\Http\Controllers\Controller;
// use App\Http\Requests\Campaign\DonateRequest;
// use App\Http\Requests\Campaign\SignRequest;
// use App\Http\Requests\Campaign\VolunteerRequest;
// use App\Http\Resources\Campaign\CampaignResource;
// use App\Models\Campaign\Campaign;
// use App\Services\Campaign\Actions\Donate;
// use App\Services\Campaign\Actions\SignPetition;
// use App\Services\Campaign\Actions\Volunteer;
// use App\Services\Campaign\DTOs\DonateData;
// use App\Services\Campaign\DTOs\SignData;
// use App\Services\Campaign\DTOs\VolunteerData;
//
// class CampaignActionsController extends Controller
// {
//    public function __construct(
//        private Donate $donate,
//        private SignPetition $sign,
//        private Volunteer $volunteer
//    ) {}
//
//    public function donate(DonateRequest $request, Campaign $campaign): CampaignResource
//    {
//        $v = $request->validated();
//
//        $data = new DonateData(
//            campaign_id: $campaign->id,
//            requester_email: $v['requester_email'],
//            amount: (float) $v['amount'],
//            currency: $v['currency'] ?? null,
//            message: $v['message'] ?? null,
//            requester_name: $v['requester_name'] ?? null,
//        );
//
//        $updated = $this->donate->handle($data);
//
//        return new CampaignResource($updated);
//    }
//
//    public function sign(SignRequest $request, Campaign $campaign): CampaignResource
//    {
//        $v = $request->validated();
//
//        $data = new SignData(
//            campaign_id: $campaign->id,
//            name: $v['name'],
//            email: $v['email'],
//        );
//
//        $updated = $this->sign->handle($data);
//
//        return new CampaignResource($updated);
//    }
//
//    public function volunteer(VolunteerRequest $request, Campaign $campaign): CampaignResource
//    {
//        $v = $request->validated();
//
//        $data = new VolunteerData(
//            campaign_id: $campaign->id,
//            name: $v['name'],
//            email: $v['email'],
//            role: $v['role'] ?? null,
//        );
//
//        $updated = $this->volunteer->handle($data);
//
//        return new CampaignResource($updated);
//    }
// }
