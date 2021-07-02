<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Paystack;
use App\Models\Transactions;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{

    /**
     * Redirect the User to Paystack Payment Page
     * @return Url
     */
    public function getPaymentAuthorizationCode(Request $request)
    {
        $validate_data = $request->validate([
            'amount' => 'required|numeric'
        ]);
        $reference = rand(10000000, 99999999);
        $secret_key = env("PAYSTACK_SECRET_KEY");
        $url = "https://api.paystack.co/transaction/initialize";
        $fields = [
          'email' => auth()->user()->email,
          'amount' => $validate_data['amount'],
          'callback_url' => "http://127.0.0.1:8000/api/payment/callback",
          'reference' => $reference,
          'currency' => 'NGN'
        ];
        $fields_string = http_build_query($fields);
        //open connection
        $ch = curl_init();
        
        //set the url, number of POST vars, POST data
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
          "Authorization: Bearer ".$secret_key,
          "Cache-Control: no-cache",
        ));
        
        //So that curl_exec returns the contents of the cURL; rather than echoing it
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, true); 
        
        //execute post
        $result = curl_exec($ch);
        $decode_result = json_decode($result, true);
        return response(['message'=>'Please paste the authorization url in a browser to make payment', 'result'=>$decode_result]);
    }

    public function verifyPayment(){
        $reference = $_GET['reference'];
        $secret_key = env("PAYSTACK_SECRET_KEY");
        $curl = curl_init();
  
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.paystack.co/transaction/verify/".$reference,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer ".$secret_key,
            "Cache-Control: no-cache",
            ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        
        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
           // $decode_response = json_decode($response, true);
            return $response;
            //echo $decode_response['data']['status'];
        }
    }
    

    /**
     * Obtain Paystack payment information
     * @return void
     */
    public function handleGatewayCallback(Request $request)
    {
        $verify_payment = $this->verifyPayment();
        $decode_response = json_decode($verify_payment, true);
        $amount = '';
        $reference = '';
        $transaction_date = '';
        $status = '';
        $transaction = '';
        if($decode_response['status'] == true){
            $status = $decode_response['data']['status'];
            $amount = $decode_response['data']['amount'];
            $reference = $decode_response['data']['reference'];
            $transaction_date = $decode_response['data']['transaction_date'];
            $check_transaction_exist = Transactions::where('reference', $reference)->get();
            if(count($check_transaction_exist) < 1){
                $transaction = Transactions::create([
                    'user_id' => auth()->user()->id,
                    'status' => $status,
                    'amount' => $amount,
                    'reference' => $reference,
                    'transaction_date' => $transaction_date
                ]);
                if($status == 'success'){
                    return response(['message' => 'Payment Successful, please note amount is in kobo', 'data' => $transaction]);
                }
                else{
                    return response(['message' => 'Payment not Successful', 'data' => $transaction]);
                }
            }else{
                return response(['message' => 'Transaction exists', 'data' => []]);
            }
        }else{
            return response(['message' => 'Wrong Transaction', 'data' => []]);
        }
    }

    public function listTransactions(){
        $user_id = auth()->user()->id;
        $get_transactions = Transactions::where('user_id', $user_id)->latest()->get();
        return response(['message' => 'Transactions fetched successfully, please note amount is in kobo', 'data' => $get_transactions]);
    }

    public function searchTransactions($search_param){
        $user_id = auth()->user()->id;
        $results = DB::select("SELECT * FROM transactions WHERE user_id = '$user_id' AND CONCAT(id, user_id, reference, amount, status, transaction_date, created_at, updated_at) LIKE '%$search_param%'");
        return response(['message' => 'Transactions fetched successfully, please note amount is in kobo', 'data' => $results]);
    }
}