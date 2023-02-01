<?php
// app/Http/Controllers/API/RegisterController.php
        namespace App\Http\Controllers\API;
 
        use Illuminate\Http\Request;
        use App\Http\Controllers\API\BaseController as BaseController;
        use App\Models\User;
        use Illuminate\Support\Facades\Auth;
        use Illuminate\Support\Str;
        use Validator;
        use Illuminate\Support\Facades\Mail;
        use App\Mail\PasswordReset;

 
        class RegisterController extends BaseController
        {
            /**
            * Register api
            *
            * @return \Illuminate\Http\Response
            */
            public function register(Request $request)
            {
                $validator = Validator::make($request->all(), [
                    'name' => 'required',
                    'email' => 'required|email',
                    'password' => 'required',
                    'c_password' => 'required|same:password',
                ]);
 
                if($validator->fails()){
                    return $this->sendError('Validation Error.', $validator->errors());       
                }

 
                $input = $request->all();
                $input['password'] = bcrypt($input['password']);
                $user = User::create($input);
                $success['token'] =  $user->createToken('MyApp')->accessToken;
                $success['name'] =  $user->name;
                

 
                return $this->sendResponse($success, 'User register successfully.');
            }
 
            /**
            * Login api
            *
            * @return \Illuminate\Http\Response
            */
            public function login(Request $request) 
            {

                $creds = $request->validate([
                    'email' => 'required|email|string|exists:users,email', 
                    'password' => ['required']
                ]);
                if (!Auth::attempt($creds)) { 
                    return response([ 'message' => 'Provided email or password is incorrect' ], 422); 
                } 
                /** @var \App\Models\User $user */
                $user = Auth::user(); 
                Auth::login($user);
                $token = $user->createToken('main')->plainTextToken; 
                return response(compact('user', 'token'))->header('Authorization', 'Bearer '.$token);

            }


            public function logout(Request $request) 
            {
                $request->user()->tokens()->delete();
                return response()->json(['message' => 'Successfully logged out']);
            }




            public function forgetPassword(Request $request) 
            {
                $validatedData = $request->validate([
                    'email' => 'required|email|string|exists:users,email', 
                ]);
                
                $user = User::whereEmail($validatedData['email'])->first();
                $token = Str::random(60);
                $user->reset_token = $token;
                $user->save();
                
                // send password reset email with the token
                Mail::to($user->email)->send(new PasswordReset($token));

                return response()->json(['message' => 'Password reset email sent']);
            }


        }