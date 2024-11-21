<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Validator;
use App\Models\User;
use App\Models\order;
use App\Models\branch;
use App\Models\ImgStep;
use App\Models\document;
use App\Models\news;
use App\Models\holiday;
use App\Models\setting;
use App\Models\payment;

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
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use PDF;
use Illuminate\Support\Facades\Mail;
use App\Mail\PDFMail;

class AuthController extends Controller
{
    public $token = true;

    public function username()
    {
        return 'phone'; // Use 'phone' instead of 'email'
    }

    public function getNews() {
        $news = news::where('startdate', '<=', Carbon::now())->get();

        // เพิ่ม URL พื้นฐานให้กับภาพ
        $baseUrl = 'https://kimspace2.sgp1.cdn.digitaloceanspaces.com/loadmaster/news/';

        // เพิ่ม URL พื้นฐานให้กับ image ของแต่ละ news
        $news->transform(function ($item) use ($baseUrl) {
            $item->image = $baseUrl . $item->image;
            return $item;
        });

        return response()->json(['news' => $news]);
    }

    public function getNewsById($id){

        $news = news::where('id', $id)->first();

        // เพิ่ม URL พื้นฐานให้กับภาพ
        $baseUrl = 'https://kimspace2.sgp1.cdn.digitaloceanspaces.com/loadmaster/news/';

        $news->image = $baseUrl . $news->image;

        return response()->json(['news' => $news]);

    }

    public function getHoliday() {
        $news = holiday::where('day', '>=', Carbon::now())->get();

        // เพิ่ม URL พื้นฐานให้กับภาพ
        $baseUrl = 'https://kimspace2.sgp1.cdn.digitaloceanspaces.com/loadmaster/holiday/';

        // เพิ่ม URL พื้นฐานให้กับ image ของแต่ละ news
        $news->transform(function ($item) use ($baseUrl) {
            $item->image = $baseUrl . $item->image;
            return $item;
        });

        return response()->json(['news' => $news]);
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
            //     ->create('+66'.$request->phone, "sms"); php artisan config:clear

           // return response()->json(['TWILIO_AUTH_TOKEN'=> $token, 'TWILIO_SID' =>  $twilio_sid]);

                $otp = rand(100000,999999);

                $message = 'Your LoadMaster verification code is: '.$otp;
                $twilio_number = '(662) 601-4809';

                $client = new Client($twilio_sid, $token);
                $client->messages->create('+66'.$request->phone, ['from' => $twilio_number, 'body' => $message]);

                $user_type = 0;
        if($request['role'] == 4){
            $user_type == 1;
        }else{
            $user_type == 0;
        }

        $email = rand(10000000,99999999)."@gmail.com";
        $user = new User();
        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->p_x = $request->password;
        $user->email = $email;
        $user->code_user = rand(10000000,99999999);
        $user->otp = $otp;
        $user->user_type = $user_type;
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

    public function sendOtp(Request $request){

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

                return response()->json([
                    'success' => true,
                    ]);

            }else{

                return response()->json([
                    'success' => false,
                    ]);
            }



        // If verification failed due to an invalid code
        return response()->json([
            'success' => false,
            'error' => 'Invalid verification code entered!',
        ], 401);

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
        ->first();

      //  return response()->json([ 'data' => $request->all(), 'user' => $user ]);

        if ($user) {
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
                      'address' => 'required',
                      'province' => 'required',
                      'timer' => 'required',
                      'admin_branch' => 'required',
                      'selectedLat2' => 'required',
                      'selectedLng2' => 'required',
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
            $objs->province = $request['province'];
            $objs->latitude = $request['selectedLat2'];
            $objs->longitude = $request['selectedLng2'];
            $objs->save();

            return response()->json([
                'success' => true,
                'branch' => $objs,
                ]);

        }catch(Exception $e){
            return response()->json(['success'=>false,'message'=>'something went wrong']);
        }

    }

    public function getProvince(Request $request){

        $validator = Validator::make($request->all(),
                      [
                      'province2' => 'required'
                     ]);

         if ($validator->fails()) {

               return response()->json(['error'=>$validator->errors()], 401);

            }

            try{

                $province = DB::table('logistics')
                    ->where('province', $request['province2'])
                    ->first();

                return response()->json([
                    'success' => true,
                    'province' => $province,
                    ]);

            }catch(Exception $e){
                return response()->json(['success'=>false,'message'=>'something went wrong']);
            }

    }

    public function formatDateThai($date)
    {
        $date = Carbon::parse($date); // เพิ่ม 2 วันจากวันที่ที่ให้มา
        $day = $date->day;
        $month = $date->format('n'); // ใช้เลขเดือน 1-12
        $year = $date->year + 543; // แปลงเป็น พ.ศ.

        // ชื่อเดือนภาษาไทย
        $thaiMonths = ["", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
        $monthThai = $thaiMonths[$month];

        return "$day $monthThai $year";
    }

    public function createOrdere(Request $request){

        $validator = Validator::make($request->all(),
                      [
                      'latitude' => 'required',
                      'longitude' => 'required',
                      'latitude2' => 'required',
                      'longitude2' => 'required',
                      'adddress' => 'required',
                      'name' => 'required',
                      'phone' => 'required',
                      'adddress2' => 'required',
                      'name2' => 'required',
                      'phone2' => 'required',
                      'size' => 'required',
                      'type' => 'required|array', // Validate as array
                      'type.*' => 'string', // Ensure each item in the array is a string
                      'weight' => 'required',
                      'province2' => 'required',
                      'province' => 'required',
                     ]);

         if ($validator->fails()) {

               return response()->json(['error'=>$validator->errors()], 401);

            }

        try{

            $user = JWTAuth::authenticate($request->token);

            $count = order::count();
            $formattedCount = str_pad($count, 6, '0', STR_PAD_LEFT);  // Result: "0025"
            $code_order = 'LM'.date('Y').''.date('m').date('d').''.$formattedCount;

            $set = DB::table('settings')
                ->where('id', 1)
                ->first();

                $taxRate = $set->tax / 100; // Convert tax rate (e.g., `1` becomes `0.01`)
                $tax = $request['price'] * $taxRate;

             //   return response()->json(['$tax'=>$tax], 401);

             if($request['branchId'] == 'undefined'){
                $branchId = 0;
             }else{
                $branchId = $request['branchId'];
             }

            $objs = new order();
            $objs->user_id = $user->id;
            $objs->branch_id = $branchId;
            $objs->code_order = $code_order;
            $objs->amount = $request['weight'];
            $objs->price = $request['price'];
            $objs->latitude = $request['latitude'];
            $objs->longitude = $request['longitude'];
            $objs->d_lat = $request['latitude'];
            $objs->d_long = $request['longitude'];
            $objs->latitude2 = $request['latitude2'];
            $objs->longitude2 = $request['longitude2'];
            $objs->adddress_re = $request['adddress2'];
            $objs->name_re = $request['name2'];
            $objs->phone_re = $request['phone2'];
            $objs->remark_re = $request['remark2'];
            $objs->province = 'จ.สมุทรปราการ';
            $objs->province2 = $request['province2'];
            $objs->size = $request['size'];
            $objs->type = implode(',', $request['type']);
            $objs->weight = $request['weight'];
            $objs->b_name = $request['name2'];
            $objs->b_address = $request['adddress2'];
            $objs->o_name = $user->name;
            $objs->b_phone = $request['phone2'];
            $objs->b_recive_name = $request['name2'];
            $objs->waffles = $request['warb'];
            $objs->machinery = $request['machinery'];
            $objs->service = $request['service'];
            $objs->service2 = $request['service2'];
            $objs->totalPrice = $request['price']-$tax;
            $objs->useDate = $this->formatDateThai(Carbon::now());
            $objs->payDate = $this->formatDateThai(Carbon::now()->addDays(2));
            $objs->save();


            return response()->json([
                'success' => true,
                'order' => $objs,
                ]);



        }catch(Exception $e){
            return response()->json(['success'=>false,'message'=>'something went wrong']);
        }

    }



    public function myLocation(Request $request){

        try{
            $user = JWTAuth::authenticate($request->token);

            $order = DB::table('orders')
            ->where('driver_id', $user->id)
            ->where('order_status', 1)
            ->first();

            if($order){

                DB::table('orders')
                    ->where('driver_id', $user->id)
                    ->where('order_status', 1)
                    ->update(
                        [
                            'd_lat' => $request->latitude,
                            'd_long' => $request->longitude,
                            'updated_at' => Carbon::now()
                            ]
                    );

                return response()->json([
                    'success' => true,
                    'order' => $order,
                    ]);

            }else{

                return response()->json([
                    'success' => false,
                    'data' => $user->id
                    ]);

            }



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

            $ImgStep = ImgStep::where('order_id', $id)->where('stepNo', 1)->get();
            $ImgStep2 = ImgStep::where('order_id', $id)->where('stepNo', 2)->get();

            $timeLine = [];

            if ($order) {
                if ($order->order_status == 1) {
                    // Timeline for code_order = 1

                    if ($order->status_dri == 1) {
                    $timeLine = [
                        [
                            'id' => '1',
                            'date' => '04-11-2024 21:56',
                            'status' => 'อยู่ระหว่างการขนส่ง',
                            'description' => 'พัสดุออกจากคลังสินค้า ไปยัง จ.สมุทรปราการ - '. $order->b_address,
                            'active' => true,
                            'icon' => 'local-shipping'
                        ],
                        [
                            'id' => '2',
                            'date' => '04-11-2024 21:56',
                            'status' => 'กำลังเตรียมพัสดุ',
                            'description' => 'คนขับรถอยู่คลังสินค้าเพื่อโหลดสินค้าขึ้นรถ',
                            'active' => false,
                            'icon' => 'inventory'
                        ],
                        [
                            'id' => '3',
                            'date' => $order->created_at,
                            'status' => 'กำลังดำเนินการ',
                            'description' => 'ระบบกำลังหาคนขับรถออกไปรับพัสดุจากคลังสินค้า',
                            'active' => false,
                            'icon' => 'pending'
                        ],
                    ];
                }else{

                    $timeLine = [
                        [
                            'id' => '1',
                            'date' => $order->time_step2,
                            'status' => 'อยู่ระหว่างการขนส่ง',
                            'description' => 'พัสดุออกจากคลังสินค้า ไปยัง จ.สมุทรปราการ - '. $order->b_address,
                            'active' => false,
                            'icon' => 'local-shipping'
                        ],
                        [
                            'id' => '2',
                            'date' => $order->time_step1,
                            'status' => 'กำลังเตรียมพัสดุ',
                            'description' => 'คนขับรถอยู่คลังสินค้าเพื่อโหลดสินค้าขึ้นรถ',
                            'active' => true,
                            'icon' => 'inventory'
                        ],
                        [
                            'id' => '3',
                            'date' => $order->created_at,
                            'status' => 'กำลังดำเนินการ',
                            'description' => 'ระบบกำลังหาคนขับรถออกไปรับพัสดุจากคลังสินค้า',
                            'active' => false,
                            'icon' => 'pending'
                        ],
                    ];

                }


                } else if ($order->order_status == 2) {
                    // Timeline for code_order = 2
                    $timeLine = [
                        [
                            'id' => '4',
                            'date' => $order->time_step3,
                            'status' => 'จัดส่งสำเร็จ',
                            'description' => 'พัสดุถูกจัดส่งสำเร็จถึงปลายทาง',
                            'active' => true,
                            'icon' => 'done'
                        ],
                        [
                            'id' => '3',
                            'date' => $order->time_step2,
                            'status' => 'อยู่ระหว่างการขนส่ง',
                            'description' => 'พัสดุออกจากคลังสินค้า ไปยัง จ.สมุทรปราการ - '. $order->b_address,
                            'active' => false,
                            'icon' => 'local-shipping'
                        ],
                        [
                            'id' => '2',
                            'date' => $order->time_step1,
                            'status' => 'กำลังเตรียมพัสดุ',
                            'description' => 'คนขับรถอยู่คลังสินค้าเพื่อโหลดสินค้าขึ้นรถ',
                            'active' => false,
                            'icon' => 'inventory'
                        ],
                        [
                            'id' => '1',
                            'date' => $order->created_at,
                            'status' => 'กำลังดำเนินการ',
                            'description' => 'ระบบกำลังหาคนขับรถออกไปรับพัสดุจากคลังสินค้า',
                            'active' => false,
                            'icon' => 'pending'
                        ],

                    ];
                } else if ($order->order_status == 0) {
                    // Timeline for code_order = 0
                    $timeLine = [
                        [
                            'id' => '1',
                            'date' => $order->created_at,
                            'status' => 'กำลังดำเนินการ',
                            'description' => 'ระบบกำลังหาคนขับรถออกไปรับพัสดุจากคลังสินค้า',
                            'active' => true,
                            'icon' => 'pending'
                        ],
                    ];
                }
            }


            return response()->json([
                'order' => $order,
                'img' => $ImgStep,
                'img2' => $ImgStep2,
                'timeline' => $timeLine
            ]);

        }catch(Exception $e){
            return response()->json(['success'=>false,'message'=>'something went wrong']);
        }

    }


    public function getOrderByIDDri(Request $request, $id){

        try{
            $user = JWTAuth::authenticate($request->token);

            $order = DB::table('orders')
            ->where('driver_id', $user->id)
            ->where('id', $id)
            ->first();

            $ImgStep = ImgStep::where('order_id', $id)->where('stepNo', 1)->get();
            $ImgStep2 = ImgStep::where('order_id', $id)->where('stepNo', 2)->get();

            $timeline = [];

            return response()->json([
                'order' => $order,
                'img' => $ImgStep,
                'img2' => $ImgStep2,
                'timeline' => $timeline
            ]);

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
            $objs = order::where('user_id', $user->id)->orderBy('id', 'desc')->get();
            return response()->json(['order' => $objs]);

        }catch(Exception $e){
            return response()->json(['success'=>false,'message'=>'something went wrong']);
        }

    }

    public function getUserOrderSuccess(Request $request){

        try{
            $user = JWTAuth::authenticate($request->token);
            $objs = order::where('user_id', $user->id)->where('order_status', 2)->where('pay_status', 0)->orderBy('id', 'desc')->get();

            $price = order::where('user_id', $user->id)->where('order_status', 2)->where('pay_status', 0)->sum('totalPrice');
            $id = 1;
            $set = setting::find($id);
            return response()->json(['order' => $objs, 'price' => $price, 'set' => $set]);

        }catch(Exception $e){
            return response()->json(['success'=>false,'message'=>'something went wrong']);
        }

    }

    public function getUserOrderCus(Request $request){

        try{
            $user = JWTAuth::authenticate($request->token);
            $objs = order::where('user_id', $user->id)->whereIn('order_status', [0,1])->orderBy('id', 'desc')->get();

            $price = order::where('user_id', $user->id)->where('order_status', 2)->where('pay_status', 0)->sum('totalPrice');

            return response()->json(['order' => $objs, 'price' => $price]);

        }catch(Exception $e){
            return response()->json(['success'=>false,'message'=>'something went wrong']);
        }

    }

    public function getUserBranch(Request $request){

        try{
            $user = JWTAuth::authenticate($request->token);
            $objs = branch::where('user_id', $user->id)->orderBy('id', 'desc')->get();
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


    // public function getOrderDri(Request $request)
    // {
    //     try{
    //         $user = JWTAuth::authenticate($request->token);

    //         $objs = order::where('driver_id', $user->id)->where('order_status', 1)->get();
    //         return response()->json(['order' => $objs]);

    //     }catch(Exception $e){
    //         return response()->json(['success'=>false,'message'=>'something went wrong']);
    //     }
    // }


    public function getOrderDri(Request $request) {
        try {
            $user = JWTAuth::authenticate($request->token);

            // Get the optional search query from the request
            $receipt = $request->query('receipt');
            $currentDate = now()->format('Y-m-d');

            // Query orders based on the driver and order status, with an optional receipt filter
            $query = order::where('driver_id', $user->id)->where('dri_date', '>=', $currentDate);

            if ($receipt) {
                $query->where('code_order', 'like', "%$receipt%");
            }

            $objs = $query->get();

            return response()->json([
                'order' => $objs,
                'msgStatus' => 200,
                'date' => $currentDate
            ]);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'something went wrong']);
        }
    }


    public function searchOrder(Request $request){

        try{
            $user = JWTAuth::authenticate($request->token);

            $objs = order::where('driver_id', $user->id)->where('code_order', 'like', $request->search)->first();
            return response()->json(['order' => $objs]);

        }catch(Exception $e){
            return response()->json(['success'=>false,'message'=>'something went wrong']);
        }

    }


    public function updateReceipt(Request $request)
    {
        try {
            $user = JWTAuth::authenticate($request->token);

            // Update the user's profile
            $user->Receiptname = $request->Receiptname;
            $user->Receiptphone = $request->Receiptphone;
            $user->Receiptemail = $request->Receiptemail;
            $user->Receiptaddress = $request->Receiptaddress;
            $user->ReceiptTax = $request->ReceiptTax;
            $user->ReceiptStatus = 1;
            $user->save();

            $userx = JWTAuth::authenticate($request->token);

            return response()->json([
                'user' => $userx,
                'msgStatus' => 200,
                'message' => 'Profile updated successfully',
                'id' => $userx->id,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
            ], 500);
        }
    }


    public function updateProfile(Request $request)
{
    try {
        $user = JWTAuth::authenticate($request->token);

        // Validate the incoming request
        $validatedData = $request->validate([
            'name' => 'required',
        ]);

        // Update the user's profile
        $user->update($validatedData);

        return response()->json([
            'user' => $user,
            'msgStatus' => 200,
            'message' => 'Profile updated successfully'
        ]);

    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong',
        ], 500);
    }
}


public function postCancelDanger(Request $request)
{
    try {
        $user = JWTAuth::authenticate($request->token);

        // Fetch the order by id
        $order = order::where('id', $request->id)->where('driver_id', $user->id)->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        // Update order fields
        $order->order_status = 1;
        $order->remark_dri3 = null;
        $order->selectedLat_dan = null;
        $order->selectedLng_dan = null;
        $order->province_dan = null;
        $order->save();

        // Delete all images associated with stepNo 3
        $imagesToDelete = ImgStep::where('order_id', $order->id)->where('stepNo', 3)->get();

        foreach ($imagesToDelete as $image) {
            // Remove the image path from the DigitalOcean Space
            $trimmed = str_replace(
                'https://kimspace2.sgp1.cdn.digitaloceanspaces.com/loadmaster/job/',
                '',
                $image->image
            );

            $storage = Storage::disk('do_spaces');
            $storage->delete('loadmaster/job/' . $trimmed);

            // Delete the image record from the database
            $image->delete();
        }


        $orderx = order::where('id', $request->id)->where('driver_id', $user->id)->first();

        return response()->json([
            'msgStatus' => 200,
            'message' => 'ยกเลิกแจ้งเหตุสำเร็จแล้ว',
            'order' => $orderx
        ]);

    } catch (Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Something went wrong',
        ], 500);
    }
}

    public function postDoc(Request $request){

        try{
            $user = JWTAuth::authenticate($request->token);

            $doc = document::where('stepNo', $request->stepNo)->where('user_id', $user->id)->first();

            if ($doc && $doc->name) {

                    // Delete the image from DigitalOcean Spaces

                    $trimmed = str_replace(
                        'https://kimspace2.sgp1.cdn.digitaloceanspaces.com/loadmaster/doc/',
                        '',
                        $doc->name
                    );

                    $storage = Storage::disk('do_spaces');
                    $storage->delete('loadmaster/doc/'. $trimmed, 'public'); // Assuming 'image' holds the path

                    // Delete the image record from the database
                    $doc->delete();

            }

             // Ensure the file is uploaded before proceeding
            if ($request->hasFile('images')) {
                $myImg = $request->file('images');
            } else {
                return response()->json(['success' => false, 'message' => 'No image file found.']);
            }


            // Resize the image to 800x800 while keeping the aspect ratio
            $img = Image::make($myImg->getRealPath());
            $img->resize(800, 800, function ($constraint) {
                $constraint->aspectRatio();
            });
            $img->stream(); // Prepare image for upload

            // Generate a unique filename
            $filename = time() . '_' . $myImg->getClientOriginalName();

            // Upload the image to DigitalOcean Spaces
            Storage::disk('do_spaces')->put(
                'loadmaster/doc/' . $filename,
                $img->__toString(),  // Ensure the image is in string format
                'public' // Make the file publicly accessible
            );

            // Save image info to ImgStep model
            $imgStep = new document();
            $imgStep->user_id = $user->id;
            $imgStep->stepNo = $request->stepNo;
            $imgStep->name = 'https://kimspace2.sgp1.cdn.digitaloceanspaces.com/loadmaster/doc/' . $filename;
            $imgStep->save();

            return response()->json([
                'msgStatus' => 200
            ]);

        }catch(Exception $e){
            return response()->json(['success'=>false,'message'=>'something went wrong']);
        }

    }


    public function UpAvatar(Request $request){

        try{
            $user = JWTAuth::authenticate($request->token);

             // Ensure the file is uploaded before proceeding
             if ($request->hasFile('images')) {
                $myImg = $request->file('images');
            } else {
                return response()->json(['success' => false, 'message' => 'No image file found.']);
            }

                // Resize the image to 800x800 while keeping the aspect ratio
                $img = Image::make($myImg->getRealPath());
                $img->resize(300, 300, function ($constraint) {
                    $constraint->aspectRatio();
                });
                $img->stream(); // Prepare image for upload

                // Generate a unique filename
                $filename = time() . '_' . $myImg->getClientOriginalName();

                // Upload the image to DigitalOcean Spaces
                Storage::disk('do_spaces')->put(
                    'loadmaster/avatar/' . $filename,
                    $img->__toString(),  // Ensure the image is in string format
                    'public' // Make the file publicly accessible
                );

                // Save image info to the user's profile
                $user->avatar = 'https://kimspace2.sgp1.cdn.digitaloceanspaces.com/loadmaster/avatar/' . $filename;
                $user->save();

                return response()->json([
                    'msgStatus' => 200,
                    'user' => $user,
                    'message' => 'Avatar updated successfully.'
                ]);


        }catch(Exception $e){
            return response()->json(['success'=>false,'message'=>'something went wrong']);
        }

    }

    public function cancelInvoice(Request $request){

        try{
            $user = JWTAuth::authenticate($request->token);

            $order = order::where('user_id', $user->id)
                    ->where('id', $request->id)
                    ->first();

            if($order){

                    $objs = order::find($request->id);
                    $objs->order_status = 4;
                    $objs->save();

                return response()->json([
                    'success' => true
                ]);

            }else{

                return response()->json([
                    'success' => false
                ]);

            }


        }catch(Exception $e){
            return response()->json(['success'=>false,'message'=>'something went wrong']);
        }

    }

    public function getPayhistory(Request $request){

        try{

            $user = JWTAuth::authenticate($request->token);

            $payment = payment::where('user_id', $user->id)->get();

            return response()->json([
                'payment' => $payment,
                'success' => true,
                'date' => Carbon::now()
            ]);

        }catch(Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage() // เพื่อช่วยในการดีบั๊ก
            ]);
        }

    }

    public function getPayhistoryById(Request $request, $id){

        try{
            $user = JWTAuth::authenticate($request->token);

            $payment = payment::where('user_id', $user->id)->where('id', $id)->first();
            $id = 1;
            $set = setting::find($id);
            $orderIds = json_decode($payment->order_id);
            $order_id_clean = str_replace(array('[', ']'), '', $orderIds);
            $order_id_clean = str_replace('"', '', $order_id_clean);
            $orderIdsArray = explode(',', $order_id_clean);
          //  dd($order_id_clean);
            $order = order::whereIn('id', $orderIdsArray)->get();

            return response()->json([
                'payment' => $payment,
                'order' => $order,
                'success' => true,
                'date' => Carbon::now(),
                'set' => $set
            ]);

        }catch(Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage() // เพื่อช่วยในการดีบั๊ก
            ]);
        }

    }

    public function postPayment(Request $request){

        try{
            $user = JWTAuth::authenticate($request->token);

            if ($request->hasFile('images')) {

                DB::beginTransaction(); // เริ่มต้นธุรกรรม
                $image = $request->file('images');

                $img = Image::make($image->getRealPath());
                    $img->resize(800, 800, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                    $img->stream(); // Prepare image for upload

                    // Generate a unique filename
                    $filename = time() . '_' . $image->getClientOriginalName();

                    // Upload the image to DigitalOcean Spaces
                    Storage::disk('do_spaces')->put(
                        'loadmaster/slip/' . $filename,
                        $img->__toString(),  // Ensure the image is in string format
                        'public' // Make the file publicly accessible
                    );

                    // สร้างรหัสการชำระเงิน
                    $count = payment::count() + 1;
                    $formattedCount = str_pad($count, 6, '0', STR_PAD_LEFT);
                    $code_payment = 'PAY' . date('Y') . date('m') . date('d') . $formattedCount;

                    // Save image info to ImgStep model
                    $payment = new payment();
                    $payment->user_id = $user->id;
                    $payment->order_id = json_encode($request->order_ids);
                    $payment->image_payment = 'https://kimspace2.sgp1.cdn.digitaloceanspaces.com/loadmaster/slip/' . $filename;
                    $payment->code_payment = $code_payment;
                    $payment->total_pay = $request->total_pay;
                    $payment->date_payment = $this->formatDateThai(Carbon::now());
                    $payment->save();

                    $orderIds = json_decode($request->order_ids, true);

                    // ตรวจสอบว่า order_ids เป็นอาร์เรย์ และอัปเดตสถานะการชำระเงินของคำสั่งซื้อ
                    if (is_array($orderIds)) {
                        foreach ($orderIds as $id) {
                            $order = Order::find($id);
                            if ($order) {
                                $order->pay_status = 1;
                                $order->save();
                            } else {
                                throw new \Exception("Order ID $id not found");
                            }
                        }
                    } else {
                        throw new \Exception("Invalid order_ids format");
                    }

            DB::commit(); // บันทึกธุรกรรมถ้าทุกอย่างสำเร็จ

                return response()->json([
                    'order' => $payment,
                    'msgStatus' => 200
                ]);

            }else{

                return response()->json([
                    'payment' => null,
                    'msgStatus' => 100
                ]);

            }

        }catch(Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage() // เพื่อช่วยในการดีบั๊ก
            ]);
        }

    }

    public function postImgStep1(Request $request){

        try{
            $user = JWTAuth::authenticate($request->token);

            $order = order::where('driver_id', $user->id)
                    ->where('id', $request->id)
                    ->first();

            //img_steps    ImgStep

            if($order){

                if($request->stepNo == 1){

                    $objs = order::find($request->id);
                    $objs->remark_dri1 = $request['remark_dri1'];
                    $objs->status_dri1 = 1;
                    $objs->time_step1 = Carbon::now();
                    $objs->save();

                }else if($request->stepNo == 2){

                    $objs = order::find($request->id);
                    $objs->remark_dri2 = $request['remark_dri1'];
                    $objs->status_dri1 = 1;
                    $objs->save();

                }else{

                    $objs = order::find($request->id);
                    $objs->remark_dri3 = $request['remark_dri1'];
                    $objs->status_dri1 = 1;
                    $objs->order_status = 3;
                    $objs->selectedLat_dan = $request['selectedLat'];
                    $objs->selectedLng_dan = $request['selectedLng'];
                    $objs->province_dan = $request['province'];
                    $objs->save();

                }


                // Handle deleted images
                if ($request->has('deletedImages')) {
                    $deletedImageIds = json_decode($request->deletedImages, true);

                    // Retrieve the images to be deleted
                    $imagesToDelete = ImgStep::whereIn('id', $deletedImageIds)->get();

                    foreach ($imagesToDelete as $image) {
                        // Delete the image from DigitalOcean Spaces

                        $trimmed = str_replace(
                            'https://kimspace2.sgp1.cdn.digitaloceanspaces.com/loadmaster/job/',
                            '',
                            $image->image
                        );

                        $storage = Storage::disk('do_spaces');
                        $storage->delete('loadmaster/job/'. $trimmed, 'public'); // Assuming 'image' holds the path

                        // Delete the image record from the database
                        $image->delete();
                    }
                }

                // Handle multiple image uploads
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    // Resize the image to 800x800 while keeping the aspect ratio
                    $img = Image::make($image->getRealPath());
                    $img->resize(800, 800, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                    $img->stream(); // Prepare image for upload

                    // Generate a unique filename
                    $filename = time() . '_' . $image->getClientOriginalName();

                    // Upload the image to DigitalOcean Spaces
                    Storage::disk('do_spaces')->put(
                        'loadmaster/job/' . $filename,
                        $img->__toString(),  // Ensure the image is in string format
                        'public' // Make the file publicly accessible
                    );

                    // Save image info to ImgStep model
                    $imgStep = new ImgStep();
                    $imgStep->driver_id = $user->id;
                    $imgStep->order_id = $order->id;
                    $imgStep->image = 'https://kimspace2.sgp1.cdn.digitaloceanspaces.com/loadmaster/job/' . $filename;
                    $imgStep->stepNo = $request->stepNo;
                    $imgStep->address = $request->getAddress;
                    $imgStep->save();
                }
            }

                return response()->json([
                    'order' => $order,
                    'msgStatus' => 200
                ]);

            }else{

                return response()->json([
                    'order' => null,
                    'msgStatus' => 100
                ]);

            }

        }catch(Exception $e){
            return response()->json(['success'=>false,'message'=>'something went wrong']);
        }

    }

    public function getImgDoc(Request $request, $id){

        try{
            $user = JWTAuth::authenticate($request->token);

            $Image = document::where('stepNo', $id)->where('user_id', $user->id)->first();

            if($Image){

                return response()->json([
                    'img' => $Image,
                    'dataStatus' => 200
                ]);

            }else{

                return response()->json([
                    'img' => null,
                    'dataStatus' => 201
                ]);

            }



        }catch(Exception $e){
            return response()->json(['success'=>false,'message'=>'something went wrong']);
        }

    }

    public function getImgStep1(Request $request, $id){

        try{
            $user = JWTAuth::authenticate($request->token);

            $order = order::where('id', $id)->first();
            $Image = ImgStep::where('order_id', $id)->where('stepNo', 1)->get();
            return response()->json([
                'order' => $order,
                'img' =>$Image
            ]);

        }catch(Exception $e){
            return response()->json(['success'=>false,'message'=>'something went wrong']);
        }

    }

    public function getImgStep2(Request $request, $id){

        try{
            $user = JWTAuth::authenticate($request->token);

            $order = order::where('id', $id)->first();
            $Image = ImgStep::where('order_id', $id)->where('stepNo', 2)->get();
            return response()->json([
                'order' => $order,
                'img' =>$Image
            ]);

        }catch(Exception $e){
            return response()->json(['success'=>false,'message'=>'something went wrong']);
        }

    }

    public function getImgStep3(Request $request, $id){

        try{
            $user = JWTAuth::authenticate($request->token);

            $order = order::where('id', $id)->first();
            $Image = ImgStep::where('order_id', $id)->where('stepNo', 3)->get();
            return response()->json([
                'order' => $order,
                'img' =>$Image
            ]);

        }catch(Exception $e){
            return response()->json(['success'=>false,'message'=>'something went wrong']);
        }

    }

    public function getHistory(Request $request) {
        try {
            $user = JWTAuth::authenticate($request->token);

            // Get the optional search query from the request
            $receipt = $request->query('receipt');

            // Query orders based on the driver and order status, with an optional receipt filter
            $query = order::where('driver_id', $user->id)->orderBy('id', 'desc');

            if ($receipt) {
                $query->where('code_order', 'like', "%$receipt%");
            }


            $objs = $query->get();

            return response()->json([
                'order' => $objs,
                'msgStatus' => 200
            ]);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'something went wrong']);
        }
    }

    public function getSetting(){

        try {
        $set = DB::table('settings')
                ->select('box_service1', 'box_service2', 'box_service3', 'tax')
                ->where('id', 1)
                ->first();

        return response()->json(['set' => $set ]);

        } catch (\Exception $e) {
            \Log::error('PDF Generation Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Something went wrong'], 500);
        }

    }


    public function generatePDFtoMail(Request $request)
{
    try {
        // Authenticate user from JWT token
        $user = JWTAuth::authenticate($request->token);

        // Fetch order details for the specified order ID and user ID
        $objs = order::where('id', $request->id)->where('user_id', $user->id)->first();

        if (!$objs) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $set = DB::table('settings')->where('id', 1)->first();

        if (!$set) {
            return response()->json(['success' => false, 'message' => 'Settings not found'], 404);
        }


        $taxRate = $set->tax / 100;
        $tax = $objs->price * $taxRate;

        $data = [
            'title' => $objs->code_order,
            'Receiptname' => $user->Receiptname,
            'Receiptphone' => $user->Receiptphone,
            'Receiptemail' => $user->Receiptemail,
            'Receiptaddress' => $user->Receiptaddress,
            'ReceiptTax' => $user->ReceiptTax,
            'price' => $objs->price,
            'date' => Carbon::now(),
            'code_order' => $objs->code_order,
            'created_at' => $objs->created_at,
            'taxText' => $set->tax,
            'tax' => $tax,
        ];

      //  return response()->json(['data' => $data, ]);

        $pdf = \PDF::loadView('document', $data)->setPaper('a4', 'portrait');
        $pdfContent = $pdf->output();

        $emailData = [
            'title' => 'Your Receipt',
            'body' => 'ใบเสร็จสำหรับการใช้บริการบน Load Master ของคุณในวันที่ '.$objs->created_at,
        ];

        Mail::to($user->Receiptemail)->send(new PDFMail($emailData, $pdfContent));

        return response()->json(['success' => true, 'message' => 'PDF sent to email successfully']);

    } catch (\Exception $e) {
        \Log::error('PDF Generation Error: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Something went wrong'], 500);
    }
}


    public function generatePDF(Request $request)
{
    try {
        // Authenticate user from JWT token
        $user = JWTAuth::authenticate($request->token);

        // Fetch order details for the specified order ID and user ID
        $objs = order::where('id', $request->id)->where('user_id', $user->id)->first();

        // Check if order exists
        if (!$objs) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        // Fetch tax settings
        $set = DB::table('settings')->where('id', 1)->first();

        // Check if settings exist
        if (!$set) {
            return response()->json(['success' => false, 'message' => 'Settings not found'], 404);
        }

        // Calculate tax based on rate from settings
        $taxRate = $set->tax / 100; // Convert tax rate (e.g., `1` becomes `0.01`)
        $tax = $objs->price * $taxRate;

        // Prepare data for PDF
        $data = [
            'title' => $objs->code_order,
            'Receiptname' => $user->Receiptname,
            'Receiptphone' => $user->Receiptphone,
            'Receiptemail' => $user->Receiptemail,
            'Receiptaddress' => $user->Receiptaddress,
            'ReceiptTax' => $user->ReceiptTax,
            'price' => $objs->price,
            'date' => Carbon::now(),
            'code_order' => $objs->code_order,
            'created_at' => $objs->created_at,
            'taxText' => $set->tax,
            'tax' => $tax,
        ];


        // Load the PDF view with the prepared data
        $pdf = \PDF::loadView('document', $data)
            ->setPaper('a4', 'portrait'); // Optional: Set paper size and orientation

        // Download the PDF file
        return $pdf->download($objs->code_order . '.pdf');

    } catch (\Exception $e) {
        // Log the error for debugging
        \Log::error('PDF Generation Error: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Something went wrong'], 500);
    }
}


    public function postStatusDri(Request $request) {
        try {
            // ตรวจสอบว่า token ถูกต้องหรือไม่
            $user = JWTAuth::authenticate($request->token);

            // เช็คค่า status_dri และ status_dri1
            $status = order::where('id', $request->id)
                           ->where('status_dri', 1)
                           ->where('status_dri1', 1)
                           ->first();

            // ตรวจสอบว่าทั้ง status_dri และ status_dri1 มีค่าเป็น 1 หรือไม่
            if ($status) {
                // หากทั้งสองสถานะเป็น 1 อัปเดต order_status เป็น 2
                $objs = order::find($request->id);
                $objs->order_status = 2;
                $objs->time_step3 = Carbon::now();
                $objs->save();

                return response()->json([
                    'order' => $objs,
                    'success' => true,
                    'msgStatus' => 200
                ]);
            } else {
                // กรณีที่ status_dri และ status_dri1 ไม่ครบตามเงื่อนไข
                return response()->json([
                    'success' => false,
                    'message' => 'กรุณาตรวจสอบข้อมูลให้ครบก่อน'
                ]);
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong'
            ]);
        }
    }


    public function getDoc(Request $request){
        try {
            // ตรวจสอบการ authenticate token
            $user = JWTAuth::authenticate($request->token);

            $sumDoc = Document::where('user_id', $user->id)
            ->whereBetween('stepNo', [1, 5])
            ->where('status', 1)
            ->count();

            $doc = Document::where('user_id', $user->id)
            ->whereBetween('stepNo', [1, 5])
            ->get();

            if($sumDoc == 5){
                $statusDoc = true;
            }else{
                $statusDoc = false;
            }

          return response()->json([
            'doc' => $doc,
            'verify' => $statusDoc,
            'success' => true,
            'msgStatus' => 200
        ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong'
            ]);
        }

    }


    public function notiStatus(Request $request)
    {
        try {
            // ตรวจสอบการ authenticate token
            $user = JWTAuth::authenticate($request->token);

            // ค้นหาผู้ใช้ด้วย ID ที่ระบุ
            $getUser = User::findOrFail($request->id);

            // สลับสถานะ noti ระหว่าง 1 และ 0
            $getUser->noti = $getUser->noti === 1 ? 0 : 1;

            // บันทึกการเปลี่ยนแปลงและตรวจสอบความสำเร็จ
            if ($getUser->save()) {

                $getDataUser = User::findOrFail($request->id);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'user_id' => $getUser->id,
                        'noti_status' => $getUser->noti,
                        'user' => $getDataUser
                    ],
                    'message' => 'Notification status updated successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update notification status'
                ]);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage() // เพิ่มรายละเอียดข้อผิดพลาดเพื่อการดีบัก
            ]);
        }
    }



    public function postNotiDri(Request $request){

        try{
            $user = JWTAuth::authenticate($request->token);

            $order = order::findOrFail($request->id);

              if($order->status_dri == 1){
                  $order->status_dri = 0;

              } else {
                  $order->status_dri = 1;
              }

              $order->newStatus = $request->newStatus;
              $order->time_step2 = Carbon::now();


                return response()->json([
                    'data' => [
                        'success' => $order->save(),
                    ]
                ]);



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
