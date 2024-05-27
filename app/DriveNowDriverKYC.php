<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DriveNowDriverKYC extends Model
{
    protected $fillable = ['driver_id', 'official_id', 'ghana_card_name', 'ghana_card_number', 'house_address', 'house_latitude', 'house_longitude', 'profile_picture', 'ghana_card_image','ghana_card_image_back', 'residence_image', 'water_bill_image', 'eb_bill_image', 'g1_name', 'g1_profile_image', 'g1_ghana_card_no', 'g1_ghana_card_image','g1_ghana_card_image_back', 'g1_house_address','g1_house_gps', 'g1_house_latitude', 'g1_house_longitude', 'g2_name', 'g2_profile_image', 'g2_ghana_card_no', 'g2_ghana_card_image','g2_ghana_card_image_back', 'g2_house_address','g2_house_gps', 'g2_house_latitude', 'g2_house_longitude', 'uploaded_by', 'uploaded_on', 'approved_by', 'approved_on', 'status','g1_mobile','g2_mobile'];

    public function driver()
    {
        return $this->belongsTo('App\Provider', 'driver_id','id');
    }

    public function official()
    {
        return $this->belongsTo('App\OfficialDriver', 'official_id','id');
    }

    public function approved()
    {
        return $this->belongsTo('App\Admin', 'approved_by','id');
    }

    public function uploaded()
    {
        return $this->belongsTo('App\Admin', 'uploaded_by','id');
    }

}
            