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
            $twilio_verify_sid = env("TWILIO_VERIFY_SID");
            $twilio = new Client($twilio_sid, $token);
            $twilio->verify->v2->services($twilio_verify_sid)
                ->verifications
                ->create('+66'.$request->phone, "sms");

        $email = rand(10000000,99999999)."@gmail.com";
        $user = new User();
        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->p_x = $request->password;
        $user->email = $email;
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
        ], Response::HTTP_OK);
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


    public function verify(Request $request)
{
    // Validate the input data
    $data = $request->validate([
        'verification_code' => ['required', 'numeric'],
        'phone_number' => ['required', 'string'],
    ]);

    // Get Twilio credentials from .env
    $token = env("TWILIO_AUTH_TOKEN");
    $twilio_sid = env("TWILIO_SID");
    $twilio_verify_sid = env("TWILIO_VERIFY_SID");

    // Initialize the Twilio Client
    $twilio = new Client($twilio_sid, $token);

        // Perform verification check $twilio->verify->v2->services($twilio_verify_sid)
        // $verification = $twilio->verify->v2->services($twilio_verify_sid)
        //     ->verificationChecks
        //     ->create([
        //         'to' => $data['phone_number'],
        //         'code' => $data['verification_code'],
        //     ]);


            \Log::info('Twilio Verify SID: ' . $twilio_verify_sid);

        // Check if the verification was successful
     //   if ($verification->valid) {
            // Clean phone number by removing country code (assuming Thailand's +66)
            $cleaned_phone_number = str_replace('+66', '', $data['phone_number']);

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
       // }



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
            ], Response::HTTP_UNAUTHORIZED);
        }

        return response()->json([
            'success' => true,
            'token' => $jwt_token,
            'user'=> Auth::user(),
            ]);
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
