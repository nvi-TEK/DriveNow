<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

//use the Rave Facade
use Rave;

class RaveController extends Controller
{

    /**
     * Initialize Rave payment process
     * @return void
     */
    public function initialize()
    {
        //This initializes payment and redirects to the payment gateway
        //The initialize method takes the parameter of the redirect URL
        Rave::initialize(route('callback'));

        /***
        *For more functionality you can use more methods like the one below
        *setKeys($publicKey, $secretKey) - This is used to set the puvlic and secret key incase you wat to use another one different from your .env
        *setEnvironment($env) - This is used to set to either staging or live incase you want to use something different from your .env
        *
        *setPrefix($prefix, $overrideRefWithPrefix=false) - 
        ***$prefix - To add prefix to your transaction reference eg. KC will lead to KC_hjdjghddhgd737
        ***$overrideRefWithPrefix - either true/false. True will override the autogenerate reference with $prefix/request()->ref while false will use the $prefix as your prefix
        **/

        //Rave::setKeys($publicKey, $secretKey)->setEnvironment($env)->setPrefix($prefix, $overrideRefWithPrefix=false)->initialize(route('callback'));

        //eg: Rave::setEnvironment('live')->setPrefix("flamez")->initialize(route('callback'));
        //eg: Rave::setKeys("PWHNNJ992838uhzjhjshud", "PWHNNJ992838uhzjhjshud")->setPrefix(request()->ref, true)->initialize(route('callback'));
        //eg: Rave::setKeys("PWHNNJ992838uhzjhjshud, "PWHNNJ992838uhzjhjshud")->setEnvironment('staging')->setPrefix("rave", false)->initialize(route('callback'));
    }

    /**
     * Obtain Rave callback information
     * @return void
     */
    public function callback(Request $request)
    {
        \Log::info($request->all());
        if(request()->cancelled && request()->tx_ref){
            //Handles Request if its cancelled
            //Payment might have been made before cancellation
            //This verifies if it's paid or not
            $data = Rave::requeryTransaction(request()->tx_ref)->paymentCanceled(request()->tx_ref);
        }elseif(request()->tx_ref){
            // Handle completed payments  
            $data = Rave::requeryTransaction(request()->tx_ref);
        }else{
            echo 'Stop!!! Please pass the txref parameter!';
        }

        dd($data);
        // Get the transaction from your DB using the transaction reference (txref)
        // Check if you have previously given value for the transaction. If you have, redirect to your successpage else, continue
        // Comfirm that the transaction is successful
        // Confirm that the chargecode is 00 or 0
        // Confirm that the currency on your db transaction is equal to the returned currency
        // Confirm that the db transaction amount is equal to the returned amount
        // Update the db transaction record (includeing parameters that didn't exist before the transaction is completed. for audit purpose)
        // Give value for the transaction
        // Update the transaction to note that you have given value for the transaction
        // You can also redirect to your success page from here
        
    }
}
?>