<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Validator;
use App\Models\User;
use App\Models\order;
use App\Models\branch;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;
use Twilio\Rest\Client;
use App\Models\Role;
use Carbon\Carbon;

class AuthController extends Controller
{
    public $token = true;

    public function username()
    {
        return 'phone'; // Use 'phone' instead of 'email'
    }

    public function register(Request $request)
    {

         $validator = Validator::make($request->all(),
                      [
                      'name' => 'required',
                      'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
                      'password' => 'required',
                      'c_password' => 'required|same:password',
                     ]);

         if ($validator->fails()) {

               return response()->json(['error'=>$validator->errors()], 401);

            }

            $count = DB::table('users')->where('phone', $request->phone)->count();

            if ($count > 0) {

                return response()->json(['error'=> 'หมายเลขโทรศัพท์นี้ถูกใช้งานไปแล้ว'], 401);

             }

            /* Get credentials from .env */
             $token = env("TWILIO_AUTH_TOKEN");
             $twilio_sid = env("TWILIO_SID");
            // $twilio_verify_sid = env("TWILIO_VERIFY_SID");
            // $twilio = new Client($twilio_sid, $token);
            // $twilio->verify->v2->services($twilio_verify_sid)
            //     ->verifications
            //     ->create('+66'.$request->phone, "sms");

                $otp = rand(100000,999999);

                $message = 'Your LoadMaster verification code is: '.$otp;
                $twilio_number = '(662) 601-4809';

                $client = new Client($twilio_sid, $token);
                $client->messages->create('+66'.$request->phone, ['from' => $twilio_number, 'body' => $message]);


        $email = rand(10000000,99999999)."@gmail.com";
        $user = new User();
        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->p_x = $request->password;
        $user->email = $email;
        $user->code_user = rand(10000000,99999999);
        $user->otp = $otp;
        $user->password = bcrypt($request->password);
        $user->save();

        // if ($this->token) {
        //     return $this->login($request);
        // }

        $objs = Role::where('id', $request['role'])->first();

        $user
        ->roles()
        ->attach(Role::where('name', $objs->name)->first());

        return response()->json([
            'success' => true,
            'verify' => false,
            'phone_number' => '+66'.$request->phone
        ], Response::HTTP_OK);
    }


    public function reserPass(Request $request){

        $validator = Validator::make($request->all(),
                      [
                      'phone' => 'required',
                      'password' => 'required',
                      'c_password' => 'required|same:password',
                     ]);

         if ($validator->fails()) {

               return response()->json(['error'=>$validator->errors()], 401);

            }

            $cleaned_phone_number = str_replace('+66', '', $request->phone);

            $user = DB::table('users')
        ->where('phone', $cleaned_phone_number)
        ->count();

        if($user > 0){

            DB::table('users')
            ->where('phone', $cleaned_phone_number)
            ->update(
                [
                    'password' => bcrypt($request->password),
                    'updated_at' => date('Y-m-d H:i:s')
                    ]
            );

            $users = User::where('phone', $cleaned_phone_number)->firstOrFail();
          //  dd($users->p_x);

            // If there's a login attempt, ensure data is passed correctly

            $login_data = new Request([
                'phone' => $cleaned_phone_number,
                'password' => $request->password,
            ]);

            // Call the login method (ensure the login method is properly defined)
            return $this->login($login_data);

        }

        return response()->json([
            'success' => false,
            'error' => 'Invalid phone number entered!',
        ], 401);

    }

    // public function verify(Request $request){

    //     $data = $request->validate([
    //         'verification_code' => ['required', 'numeric'],
    //         'phone_number' => ['required', 'string'],
    //     ]);
    //     /* Get credentials from .env */
    //     $token = env("TWILIO_AUTH_TOKEN");
    //     $twilio_sid = env("TWILIO_SID");
    //     $twilio_verify_sid = env("TWILIO_VERIFY_SID");
    //     $twilio = new Client($twilio_sid, $token);
    //     $verification = $twilio->verify->v2->services($twilio_verify_sid)
    //         ->verificationChecks
    //         ->create([
    //             'to' =>  $data['phone_number'], // Phone number being verified
    //             'code' => $data['verification_code'] // The verification code sent to the user
    //         ]);
    //        // ->create($data['verification_code'], array('to' => $data['phone_number']));
    //     if ($verification->valid) {

    //         $phone_number = $data['phone_number'];
    //         $cleaned_phone_number = str_replace('+66', '', $phone_number);
    //         $user = tap(User::where('phone', $cleaned_phone_number))->update(['is_verified' => true]);
    //         /* Authenticate user */
    //         //Auth::login($user->first());
    //         // return redirect()->route('home')->with(['message' => 'Phone number verified']);

    //         $data_login->phone = $data['phone_number'];
    //         $data_login->password = $data['p_x'];
    //         return $this->login($data_login);



    //     }
    //     // return back()->with(['phone_number' => $data['phone_number'], 'error' => 'Invalid verification code entered!']);

    //     return response()->json([
    //         'success' => false,
    //         'error' => 'Invalid verification code entered!'
    //         ]);

    // }

    public function reverify(Request $request)
    {

       // dd(date('Y-m-d H:i:s'));
        $data = $request->validate([
            'phone_number' => ['required', 'string'],
        ]);

        $cleaned_phone_number = str_replace('+66', '', $data['phone_number']);

        $user = DB::table('users')
        ->where('phone', $cleaned_phone_number)
        ->count();

        if($user > 0){

            $checktime = DB::table('users')
            ->where('phone', $cleaned_phone_number)
            ->first();

            //***$checktime->updated_at "2024-09-19 17:00:52"   ต้องการเช็คว่าเกิน 3 นาทีหรือยัง

            $updatedAt = Carbon::parse($checktime->updated_at);
            $now = Carbon::now();

            if ($now->diffInMinutes($updatedAt) >= 3) {

                $otp = rand(100000,999999);

            DB::table('users')
            ->where('phone', $cleaned_phone_number)
            ->update(
                [
                    'otp' => $otp,
                    'updated_at' => date('Y-m-d H:i:s')
                    ]
            );

            $token = env("TWILIO_AUTH_TOKEN");
            $twilio_sid = env("TWILIO_SID");

            $message = 'Your LoadMaster verification code is: '.$otp;
                $twilio_number = '(662) 601-4809';

                $client = new Client($twilio_sid, $token);
                $client->messages->create($data['phone_number'], ['from' => $twilio_number, 'body' => $message]);


            return response()->json([
                'success' => true,
                'pass' => 1, // ค่าแสดงว่าเกิน 3 นาทีแล้ว
                'phone_number' => $data['phone_number']
            ], Response::HTTP_OK);

            }else{

                return response()->json([
                    'success' => false,
                    'pass' => 0, // ค่าแสดงว่าเกิน 3 นาทีแล้ว
                    'phone_number' => $data['phone_number']
                ], Response::HTTP_OK);

            }



        }

        return response()->json([
            'success' => false,
            'error' => 'Invalid phone number entered!',
        ], 401);

    }




    public function sms(Request $request)
    {

        $token = env("TWILIO_AUTH_TOKEN");
        $twilio_sid = env("TWILIO_SID");

        $data = $request->validate([
            'phone_number' => ['required', 'string'],
        ]);

        $message = 'Your LoadMaster verification code is: 387921';
        $twilio_number = '(662) 601-4809';

        $client = new Client($twilio_sid, $token);
        $client->messages->create($data['phone_number'], ['from' => $twilio_number, 'body' => $message]);

        return response()->json([
            'success' => true,
            ]);

    }


    public function verify(Request $request)
    {
    // Validate the input data
    $data = $request->validate([
        'verification_code' => ['required', 'numeric'],
        'phone_number' => ['required', 'string'],
    ]);

    $cleaned_phone_number = str_replace('+66', '', $data['phone_number']);

    $user = DB::table('users')
        ->where('phone', $cleaned_phone_number)
        ->where('otp', $data['verification_code'])
        ->count();

        if ($user > 0) {
            // Clean phone number by removing country code (assuming Thailand's +66)


            // Update the user as verified based on the cleaned phone number
            $user = User::where('phone', $cleaned_phone_number)->firstOrFail();
            $user->update(['is_verified' => true]);
            //dd($user->p_x);
            // If there's a login attempt, ensure data is passed correctly
            $data['p_x'] = $user->p_x;
            $login_data = new Request([
                'phone' => $cleaned_phone_number,
                'password' => $data['p_x'],
            ]);

            // Call the login method (ensure the login method is properly defined)
            return $this->login($login_data);
        }



    // If verification failed due to an invalid code
    return response()->json([
        'success' => false,
        'error' => 'Invalid verification code entered!',
    ], 401);
}

    public function login(Request $request)
    {
        $input = $request->only('phone', 'password');
        $jwt_token = null;
      //  dd($input);



        if (!$jwt_token = JWTAuth::attempt($input)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Email or Password',
                'verify' => 2
            ], Response::HTTP_UNAUTHORIZED);
        }

       // dd(Auth::user()->is_verified);

       if(Auth::user()->is_verified === 0){

        return response()->json([
            'success' => false,
            'message' => 'please verify otp',
            'verify' => 0
        ]);

       }else{

        return response()->json([
            'success' => true,
            'token' => $jwt_token,
            'user'=> Auth::user(),
            'verify' => 1
            ]);

       }


    }

    public function userBranchCreate(Request $request){


        $validator = Validator::make($request->all(),
                      [
                      'name' => 'required',
                      'phone' => 'required',
                      'email' => 'required',
                      'address' => 'required',
                      'province' => 'required',
                      'postcode' => 'required',
                      'district' => 'required',
                      'subdistrict' => 'required',
                      'code' => 'required',
                      'timer' => 'required',
                      'admin_branch' => 'required',
                     ]);

         if ($validator->fails()) {

               return response()->json(['error'=>$validator->errors()], 401);

            }

        try{
            $user = JWTAuth::authenticate($request->token);

            $objs = new branch();
            $objs->user_id = $user->id;
            $objs->name_branch = $request['name'];
            $objs->address_branch = $request['address'];
            $objs->code_branch = $request['code'];
            $objs->phone = $request['phone'];
            $objs->admin_branch = $request['admin_branch'];
            $objs->time = $request['timer'];
            $objs->email = $request['email'];
            $objs->province = $request['province'];
            $objs->postcode = $request['postcode'];
            $objs->district = $request['district'];
            $objs->subdistrict = $request['subdistrict'];
            $objs->save();

            return response()->json([
                'success' => true,
                'branch' => $objs,
                ]);

        }catch(Exception $e){
            return response()->json(['success'=>false,'message'=>'something went wrong']);
        }

    }

    public function checkQrcode(Request $request){


        try{
            $user = JWTAuth::authenticate($request->token);

            $order = DB::table('orders')
            ->where('user_id', $user->id)
            ->where('code_order', $request->qrcode)
            ->first();

            if($order){

                return response()->json([
                    'success' => true,
                    'order' => $order,
                    ]);

            }else{

                return response()->json([
                    'success' => false,
                    ]);

            }



        }catch(Exception $e){
            return response()->json(['success'=>false,'message'=>'something went wrong']);
        }


    }


    public function getOrderByID(Request $request, $id){

        try{
            $user = JWTAuth::authenticate($request->token);

            $order = DB::table('orders')
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->first();

            return response()->json(['order' => $order]);

        }catch(Exception $e){
            return response()->json(['success'=>false,'message'=>'something went wrong']);
        }

    }

    public function logout(Request $request)
    {

        try {
            JWTAuth::invalidate(JWTAuth::parseToken($request->token));

            return response()->json([
                'success' => true,
                'message' => 'User logged out successfully'
            ]);
        } catch (JWTException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Sorry, the user cannot be logged out'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    public function getUser(Request $request)
    {
        try{
            $user = JWTAuth::authenticate($request->token);
            return response()->json(['user' => $user]);

        }catch(Exception $e){
            return response()->json(['success'=>false,'message'=>'something went wrong']);
        }
    }

    public function getUserOrder(Request $request){

        try{
            $user = JWTAuth::authenticate($request->token);
            $objs = order::where('user_id', $user->id)->get();
            return response()->json(['order' => $objs]);

        }catch(Exception $e){
            return response()->json(['success'=>false,'message'=>'something went wrong']);
        }

    }

    public function getUserBranch(Request $request){

        try{
            $user = JWTAuth::authenticate($request->token);
            $objs = branch::where('user_id', $user->id)->get();
            return response()->json(['branch' => $objs]);

        }catch(Exception $e){
            return response()->json(['success'=>false,'message'=>'something went wrong']);
        }

    }


    public function getUserBranchID(Request $request, $id){

        try{
            $user = JWTAuth::authenticate($request->token);
            $objs = branch::where('id', $id)->first();
            $order = order::where('branch_id', $id)->get();
            $objs->order = $order;
            return response()->json(['branch' => $objs]);

        }catch(Exception $e){
            return response()->json(['success'=>false,'message'=>'something went wrong']);
        }


    }



    public function refresh()
    {
        return response()->json([
            'status' => 'success',
            'user' => Auth::user(),
            'token' => Auth::refresh(),
        ]);
    }

}
