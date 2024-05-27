<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Provider;
use App\Helpers\Helper;
use Carbon\Carbon;
use App\ProviderProfile;
use App\Http\Controllers\SendPushNotification;
use Log;
use Setting;
use App\DriveNowRaveTransaction;
use App\DriveNowTransaction;
use App\OfficialDriver;
use App\DriveNowExtraPayment;
use App\DriveNowAdditionalTransactions;


class UpdateIntercom extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:intercom';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update intercom everty 5 minutes with User and Provider data';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tro_access_token = Setting::get('tro_access_token','');
        if($tro_access_token == ''){
            $time = Carbon::now()->timestamp;
            $account = ""; // Replace with account
            $password = ""; // Replace with passowrd
            $signature = md5(md5($password).$time);

            $token_url = "http://api.protrack365.com/api/authorization?time=".$time."&account=".$account."&signature=".$signature;

            $token_json = curl($token_url);

            $token_details = json_decode($token_json, TRUE);

            $tro_access_token = $token_details['record']['access_token'];
            Setting::set('tro_access_token', $tro_access_token);
            Setting::save();
            Log::info("Tro Access Token Called");
        }

        $credit_pending_transactions = DriveNowRaveTransaction::where('status', 2)->orderBy('created_at', 'desc')->get();
        // whereDate('created_at', Carbon::today())->where('status', 2)->;

            foreach ($credit_pending_transactions as $credit_pending_transaction) {
                $CP = Helper::ConfirmPayment($credit_pending_transaction->id);
            }
      

        app('App\Http\Controllers\Intercom\IntercomController')->index();//providers
        app('App\Http\Controllers\Intercom\IntercomController')->users();//users
    }
}
