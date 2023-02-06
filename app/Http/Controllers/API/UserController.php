<?php

namespace App\Http\Controllers\API;

use DB;
use Log;
use File;
use Hash;
use Storage;
use App\Like;
use App\Post;
use App\User;
use App\Admin;
use App\Common;
use App\Report;
use App\Bookmark;
use App\Comments;
use App\BlockUser;
use App\Followers;
use App\Notification;
use App\RedeemRequest;
use App\GlobalFunction;
use App\ProfileCategory;
use Laravel\Passport\Token;
use App\VerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Classes\AgoraDynamicKey\RtcTokenBuilder;

class UserController extends Controller
{
    public function generateAgoraToken(Request $request)
    {
        $headers = $request->headers->all();

        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }
        $rules = [
            'channelName' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => false, 'message' => $msg]);
        }
        $appID = env('AGORA_APP_ID');
        $appCertificate = env('AGORA_APP_CERT');
        $channelName = $request->channelName;
        $role = RtcTokenBuilder::RolePublisher;
        $expireTimeInSeconds = 7200;
        $currentTimestamp = now()->getTimestamp();
        $privilegeExpiredTs = $currentTimestamp + $expireTimeInSeconds;
        $token = RtcTokenBuilder::buildTokenWithUid($appID, $appCertificate, $channelName, 0, $role, $privilegeExpiredTs);

        return json_encode(['status' => 200, 'message' => "token generated successfully", 'token' => $token]);
    }

    public function Registration(Request $request)
    {

        $code = rand(1000, 9999);

        $headers = $request->headers->all();

        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'full_name' => 'required',
            'user_email' => 'required',
            'device_token' => 'required',
            'user_name' => 'required', //|unique:tbl_users
            'identity' => 'required',
            'login_type' => 'required',
            'platform' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }

        $CheckUSer =  User::where('identity', $request->get('identity'))->first();

        if (empty($CheckUSer)) {

            $data['full_name'] = $request->get('full_name');
            $data['user_email'] = $request->get('user_email');
            $data['device_token'] = $request->get('device_token');
            $data['invite_code'] = $code;
            $data['user_name'] = Common::generateUniqueUserId();
            $data['identity'] = $request->get('identity');
            $data['login_type'] = $request->get('login_type');
            $data['platform'] = $request->get('platform');

        $result = User::insert($data);

            if (!empty($result)) {
                $user_id = DB::getPdo()->lastInsertId();
                $User =  User::where('user_id', $user_id)->first();

                $User['token'] = 'Bearer ' . $User->createToken('Livefans')->accessToken;
                $User['followers_count'] = Followers::where('to_user_id', $user_id)->count();
                $User['following_count'] = Followers::where('from_user_id', $user_id)->count();
                $User['my_post_likes'] = Post::select('tbl_post.*')->leftjoin('tbl_likes as l', 'l.post_id', 'tbl_post.post_id')->where('tbl_post.user_id', $user_id)->count();
                $profile_category_data = ProfileCategory::where('profile_category_id', $User->profile_category)->first();
                $User['profile_category_name'] = !empty($profile_category_data) ? $profile_category_data['profile_category_name'] : "";
                unset($User->timezone);
                unset($User->created_at);
                unset($User->updated_at);

                return response()->json(['status' => 200, 'message' => "User Registered Successfully.", 'data' => $User]);
            } else {
                return response()->json(['status' => 401, 'message' => "Error While User Registration"]);
            }
        } else {
            $identity = $request->get('identity');
            $data['device_token'] = $request->get('device_token');
            $data['invite_code'] = $code;
            $data['login_type'] = $request->get('login_type');
            $data['platform'] = $request->get('platform');

            $user_id = $CheckUSer->user_id;
            $result =  User::where('identity', $identity)->update($data);

            $User =  User::where('user_id', $user_id)->first();
            $User['platform'] = $User->platform ? (int)$User->platform : 0;
            $User['is_verify'] = $User->is_verify ? (int)$User->is_verify : 0;
            $User['total_received'] = $User->total_received ? (int)$User->total_received : 0;
            $User['total_send'] = $User->total_send ? (int)$User->total_send : 0;
            $User['my_wallet'] = $User->my_wallet ? (int)$User->my_wallet : 0;
            $User['spen_in_app'] = $User->spen_in_app ? (int)$User->spen_in_app : 0;
            $User['check_in'] = $User->check_in ? (int)$User->check_in : 0;
            $User['upload_video'] = $User->upload_video ? (int)$User->upload_video : 0;
            $User['from_fans'] = $User->from_fans ? (int)$User->from_fans : 0;
            $User['purchased'] = $User->purchased ? (int)$User->purchased : 0;

            $User['status'] = $User->status ? (int)$User->status : 0;
            $User['freez_or_not'] = $User->freez_or_not ? (int)$User->freez_or_not : 0;

            $User['token'] = 'Bearer ' . $User->createToken('Livefans')->accessToken;
            $User['followers_count'] = Followers::where('to_user_id', $user_id)->count();
            $User['following_count'] = Followers::where('from_user_id', $user_id)->count();
            $User['my_post_likes'] = Post::select('tbl_post.*')->leftjoin('tbl_likes as l', 'l.post_id', 'tbl_post.post_id')->where('tbl_post.user_id', $user_id)->count();
            $profile_category_data = ProfileCategory::where('profile_category_id', $User->profile_category)->first();
            $User['profile_category_name'] = !empty($profile_category_data) ? $profile_category_data['profile_category_name'] : "";
            $User['user_mobile_no'] = $User->user_mobile_no ? $User->user_mobile_no : "";
            $User['user_profile'] = $User->user_profile ? $User->user_profile : "";
            $User['bio'] = $User->bio ? $User->bio : "";
            $User['profile_category'] = $User->profile_category ? $User->profile_category : "";
            $User['fb_url'] = $User->fb_url ? $User->fb_url : "";
            $User['insta_url'] = $User->insta_url ? $User->insta_url : "";
            $User['youtube_url'] = $User->youtube_url ? $User->youtube_url : "";

            unset($User->timezone);
            unset($User->created_at);
            unset($User->updated_at);

            return response()->json(['status' => 200, 'message' => "User registered successfully.", 'data' => $User]);
        }
    }

    public function inviteLink(Request $request)
    {
        $headers = $request->headers->all();

        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $user_id = auth()->user()->user_id;
        $inviteCode = User::select('invite_code')->where('user_id',$user_id)->first();
        if($inviteCode) {
            $inviteUrl = env('APP_URL').'/invite-code='.$inviteCode->invite_code;
            return response()->json(['status' => 200, 'message' => "Inivitation url generated successfully.", 'data' => $inviteUrl]);
        }
        return response()->json(['status' => 401, 'message' => "Inivitation url is not generated"]);

    }

    public function inviteCodeVerifiy(Request $request)
    {
        $headers = $request->headers->all();

        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $inviteLogin = User::select('invite_login' , 'limit_of_invite_code')->where('user_id',$request->user_id)->first();

        if($inviteLogin->invite_login == 1 || $inviteLogin->limit_of_invite_code == 0){

            return response()->json(['success_code' => 201, 'message' => "This invitation code is already used."]);

        }else{

            $r = parse_url($request->url);
            $inviteCode = substr($r['path'], strpos($r['path'],"=") + 1);
            $checkInivitationCode = User::where('invite_code',$inviteCode)->first();

            if(isset($checkInivitationCode)){

                $updateInivationcodeCount = User::where('invite_code',$inviteCode)->update([
                    'limit_of_invite_code' => DB::raw('limit_of_invite_code-1'),
                    'invite_login' => '1'
                ]);
                if($updateInivationcodeCount){
                    return response()->json(['success_code' => 200, 'message' => "Inivitation code verified successfully"]);
                }else{
                    return response()->json(['success_code' => 401, 'message' => "Inivitation code is not verified"]);
                }

            }else{

                return response()->json(['success_code' => 401, 'message' => "Invalid Invitation Code"]);

            }

        }
        return response()->json(['status' => 401, 'message' => "Inivitation url is not generated"]);

    }

    public function Logout()
    {


        if (Auth::check()) {
            $user = Auth::user();
            $accessToken = Auth::user()->token();
            if (isset($user->user_id)) {
                DB::table('oauth_access_tokens')->where('id', $accessToken->id)->delete();
                $data['device_token'] = "";
                $data['platform'] = 0;
                $result =  User::where('user_id', $user->user_id)->update($data);
                return response()->json(['success_code' => 200, 'response_code' => 1, 'response_message' => "User logout successfully."]);
            } else {
                return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => "User Id is required"]);
            }
        } else {
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => "User Id is required"]);
        }
    }

    public function verifyRequest(Request $request)
    {

        $user_id = $request->user()->user_id;

        if (empty($user_id)) {
            $msg = "user id is required";
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        }


        $headers = $request->headers->all();

        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'id_number' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }

        $User =  User::where('user_id', $user_id)->first();

        $count_approve = VerificationRequest::where('user_id', $user_id)->where('status', 1)->count();

        if ($count_approve >= 1) {
            return response()->json(['status' => 200, 'message' => "Verification request already aproved."]);
        }

        $count_pending = VerificationRequest::where('user_id', $user_id)->where('status', 0)->count();

        if ($count_pending == 1) {
            return response()->json(['status' => 200, 'message' => "Your Verification request pending."]);
        }

        $id_number = $request->get('id_number') ? $request->get('id_number') : '';
        $name = $request->get('name') ? $request->get('name') : '';
        $address = $request->get('address') ? $request->get('address') : '';
        $photo_id_image = "";

        if ($request->hasfile('photo_id_image')) {
            $file = $request->file('photo_id_image');
            $photo_id_image = GlobalFunction::uploadFilToS3($file);
        }

        $photo_with_id_image = "";

        if ($request->hasfile('photo_with_id_image')) {
            $file = $request->file('photo_with_id_image');
            $photo_with_id_image = GlobalFunction::uploadFilToS3($file);
        }

        $data = array(
            'id_number' => $id_number,
            'user_id' => $user_id,
            'name' => $name,
            'address' => $address,
            'photo_id_image' => $photo_id_image,
            'photo_with_id_image' => $photo_with_id_image,
        );

        $result = VerificationRequest::insert($data);
        $data1['is_verify'] = 2;
        User::where('user_id', $user_id)->update($data1);
        if (!empty($result)) {
            return response()->json(['status' => 200, 'message' => "Verification request successfully send."]);
        } else {
            return response()->json(['status' => 401, 'message' => "Verification request send failed."]);
        }
    }

    function checkUsername(Request $request)
    {

        $user_id = $request->user()->user_id;

        if (empty($user_id)) {
            $msg = "user id is required";
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        }

        $headers = $request->headers->all();

        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'user_name' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }

        $user_name = $request->get('user_name');
        $result =  User::where('user_name', $user_name)->first();

        if (empty($result)) {
            return response()->json(['status' => 200, 'message' => "Username generet successfully"]);
        } else {
            return response()->json(['status' => 401, 'message' => "Username already exist"]);
        }
    }

    function getProfile(Request $request)
    {

        // $user_id = $request->user()->user_id;

        // if (empty($user_id)) {
        //     $msg = "user id is required";
        //     return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        // }


        $headers = $request->headers->all();

        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }
        $user_id = $request->user_id;

        $User =  User::where('user_id', $user_id)->first();
        if (empty($User)) {
            return response()->json(['status' => 401, 'message' => "User Not Found"]);
        }

        $my_user_id = $request->my_user_id;
        $User->is_following_eachOther = 0;

        if ($request->has('my_user_id')) {
            $myUser = User::where('user_id', $request->my_user_id)->first();
            if ($myUser == null) {
                return response()->json(['status' => false, 'message' => "My User doesn't exists !"]);
            }
            $my_user_id = $myUser->user_id;

            // Is following each other
            $follow = Followers::where('from_user_id', $myUser->user_id)->where('to_user_id', $User->user_id)->first();

            $follow2 = Followers::where('from_user_id', $User->user_id)->where('to_user_id', $myUser->user_id)->first();

            if ($follow2 == null || $follow == null) {
                $User->is_following_eachOther = 0;
            } else {
                $User->is_following_eachOther = 1;
            }
        }


        $is_count = Followers::where('from_user_id', $my_user_id)->where('to_user_id', $user_id)->count();

        if ($is_count > 0) {
            $is_count = 1;
        } else {
            $is_count = 0;
        }
        $is_block = BlockUser::where('from_user_id', $my_user_id)->where('block_user_id', $user_id)->count();

        if ($is_block > 0) {
            $is_block = 1;
        } else {
            $is_block = 0;
        }

        $User['platform'] = $User->platform ? (int)$User->platform : 0;
        $User['is_verify'] = $User->is_verify ? (int)$User->is_verify : 0;
        $User['total_received'] = $User->total_received ? (int)$User->total_received : 0;
        $User['total_send'] = $User->total_send ? (int)$User->total_send : 0;
        $User['my_wallet'] = $User->my_wallet ? (int)$User->my_wallet : 0;
        $User['spen_in_app'] = $User->spen_in_app ? (int)$User->spen_in_app : 0;
        $User['check_in'] = $User->check_in ? (int)$User->check_in : 0;
        $User['upload_video'] = $User->upload_video ? (int)$User->upload_video : 0;
        $User['from_fans'] = $User->from_fans ? (int)$User->from_fans : 0;
        $User['purchased'] = $User->purchased ? (int)$User->purchased : 0;

        $User['status'] = $User->status ? (int)$User->status : 0;
        $User['freez_or_not'] = $User->freez_or_not ? (int)$User->freez_or_not : 0;

        $User['followers_count'] = Followers::where('to_user_id', $user_id)->count();
        $User['following_count'] = Followers::where('from_user_id', $user_id)->count();
        $myPostIds = Post::where('user_id', $user_id)->pluck('post_id');
        $myPostLikeCount = Like::whereIn('post_id', $myPostIds)->count();
        $User['my_post_likes'] = $myPostLikeCount;

        $profile_category_data = ProfileCategory::where('profile_category_id', $User->profile_category)->first();
        $User['profile_category_name'] = !empty($profile_category_data) ? $profile_category_data['profile_category_name'] : "";
        $User['is_following'] = (int)$is_count;
        $User['block_or_not'] = (int)$is_block;
        $User['user_profile'] = $User->user_profile ? $User->user_profile : "";
        $User['user_mobile_no'] = $User->user_mobile_no ? $User->user_mobile_no : "";
        $User['bio'] = $User->bio ? $User->bio : "";
        $User['profile_category'] = $User->profile_category ? $User->profile_category : "";
        $User['fb_url'] = $User->fb_url ? $User->fb_url : "";
        $User['insta_url'] = $User->insta_url ? $User->insta_url : "";
        $User['youtube_url'] = $User->youtube_url ? $User->youtube_url : "";

        unset($User->status);
        unset($User->freez_or_not);
        unset($User->timezone);
        unset($User->created_at);
        unset($User->updated_at);

        return response()->json(['status' => 200, 'message' => "User Profile Get successfully.", 'data' => $User]);
    }

    public function updateProfile(Request $request)
    {

        $user_id = $request->user()->user_id;

        if (empty($user_id)) {
            $msg = "user id is required";
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        }

        $headers = $request->headers->all();

        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }


        $rules = [
            'full_name' => 'required',
            'user_name' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }

        $CheckUSer =  User::where('user_id', $user_id)->first();
        if (empty($CheckUSer)) {
            return response()->json(['status' => 401, 'message' => "User Not Found"]);
        }
        if ($request->hasfile('user_profile')) {
            $file = $request->file('user_profile');
            $data['user_profile'] = GlobalFunction::uploadFilToS3($file);
        }

        if (!empty($request->get('full_name'))) {
            $data['full_name'] = $request->get('full_name');
        }
        if (!empty($request->get('user_email'))) {
            $data['user_email'] = $request->get('user_email');
        }
        if (!empty($request->get('user_name'))) {
            $data['user_name'] = $request->get('user_name');
        }
        if (!empty($request->get('user_mobile_no'))) {
            $data['user_mobile_no'] = $request->get('user_mobile_no');
        }
        if (!empty($request->get('profile_category'))) {
            $data['profile_category'] = $request->get('profile_category');
        }
        if (!empty($request->get('bio'))) {
            $data['bio'] = $request->get('bio');
        }
        if (!empty($request->get('fb_url'))) {
            $data['fb_url'] = $request->get('fb_url');
        }
        if (!empty($request->get('insta_url'))) {
            $data['insta_url'] = $request->get('insta_url');
        }
        if (!empty($request->get('youtube_url'))) {
            $data['youtube_url'] = $request->get('youtube_url');
        }

        $result =  User::where('user_id', $user_id)->update($data);
        if (!empty($result)) {

            $User =  User::where('user_id', $user_id)->first();

            $User['platform'] = $User->platform ? (int)$User->platform : 0;
            $User['is_verify'] = $User->is_verify ? (int)$User->is_verify : 0;
            $User['total_received'] = $User->total_received ? (int)$User->total_received : 0;
            $User['total_send'] = $User->total_send ? (int)$User->total_send : 0;
            $User['my_wallet'] = $User->my_wallet ? (int)$User->my_wallet : 0;
            $User['spen_in_app'] = $User->spen_in_app ? (int)$User->spen_in_app : 0;
            $User['check_in'] = $User->check_in ? (int)$User->check_in : 0;
            $User['upload_video'] = $User->upload_video ? (int)$User->upload_video : 0;
            $User['from_fans'] = $User->from_fans ? (int)$User->from_fans : 0;
            $User['purchased'] = $User->purchased ? (int)$User->purchased : 0;

            $User['status'] = $User->status ? (int)$User->status : 0;
            $User['freez_or_not'] = $User->freez_or_not ? (int)$User->freez_or_not : 0;

            $User['followers_count'] = Followers::where('to_user_id', $user_id)->count();
            $User['following_count'] = Followers::where('from_user_id', $user_id)->count();
            $User['my_post_likes'] = Post::select('tbl_post.*')->leftjoin('tbl_likes as l', 'l.post_id', 'tbl_post.post_id')->where('tbl_post.user_id', $user_id)->count();
            $profile_category_data = ProfileCategory::where('profile_category_id', $User->profile_category)->first();
            $User['profile_category_name'] = !empty($profile_category_data) ? $profile_category_data['profile_category_name'] : "";

            $User['user_profile'] = $User->user_profile ? $User->user_profile : "";
            $User['user_mobile_no'] = $User->user_mobile_no ? $User->user_mobile_no : "";
            $User['bio'] = $User->bio ? $User->bio : "";
            $User['profile_category'] = $User->profile_category ? $User->profile_category : "";
            $User['fb_url'] = $User->fb_url ? $User->fb_url : "";
            $User['insta_url'] = $User->insta_url ? $User->insta_url : "";
            $User['youtube_url'] = $User->youtube_url ? $User->youtube_url : "";

            unset($User->timezone);
            unset($User->created_at);
            unset($User->updated_at);

            return response()->json(['status' => 200, 'message' => "User details update successfully", 'data' => $User]);
        } else {
            return response()->json(['status' => 401, 'message' => "Error While User Profile Update", 'data' => []]);
        }
    }



    public function deleteMyAccount(Request $request)
    {

        $user_id = $request->user()->user_id;

        if (empty($user_id)) {
            $msg = "user id is required";
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        }

        $headers = $request->headers->all();

        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $CheckUSer =  User::where('user_id', $user_id)->first();
        if (empty($CheckUSer)) {
            return response()->json(['status' => 401, 'message' => "User Not Found"]);
        }
        $result =  User::where('user_id', $user_id)->delete();
        Post::where('user_id', $user_id)->delete();
        Bookmark::where('user_id', $user_id)->delete();
        Comments::where('user_id', $user_id)->delete();
        Followers::where('from_user_id', $user_id)->orWhere('to_user_id', $user_id)->delete();
        Like::where('user_id', $user_id)->delete();
        RedeemRequest::where('user_id', $user_id)->delete();
        Report::where('user_id', $user_id)->delete();
        VerificationRequest::where('user_id', $user_id)->delete();
        Notification::where('received_user_id', $user_id)->orWhere('sender_user_id', $user_id)->orWhere('item_id', $user_id)->delete();

        if ($result) {
            return response()->json(['status' => 200, 'message' => "User Account Deleted successfully"]);
        } else {
            return response()->json(['status' => 401, 'message' => "Error While User Account Delete", 'data' => []]);
        }
    }

    public function getNotificationList(Request $request)
    {


        $user_id = $request->user()->user_id;
        if (empty($user_id)) {
            $msg = "user id is required";
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        }

        $headers = $request->headers->all();
        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'start' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }

        $limit = $request->get('limit') ? $request->get('limit') : 20;
        $start = $request->get('start') ? $request->get('start') : 0;

        $NotificationData  = Notification::where('received_user_id', $user_id)->orderBy('notification_id', 'DESC')
            ->with(['sender_user'])
            ->offset($start)
            ->limit($limit)
            ->get();

        return response()->json(['status' => 200, 'message' => "Notification Data Get Successfully.", 'data' => $NotificationData]);
    }

    public function setNotificationSettings(Request $request)
    {


        $user_id = $request->user()->user_id;
        if (empty($user_id)) {
            $msg = "user id is required";
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        }

        $headers = $request->headers->all();
        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'device_token' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }

        $device_token = $request->get('device_token') ? $request->get('device_token') : "";
        $data['device_token'] = $device_token;
        $result  = User::where('user_id', $user_id)->update($data);

        if ($result) {
            return response()->json(['status' => 200, 'message' => "Setting Update Successfully"]);
        } else {
            return response()->json(['status' => 401, 'message' => "Error While Setting Update"]);
        }
    }

    public function getProfileCategoryList(Request $request)
    {



        $user_id = $request->user()->user_id;
        if (empty($user_id)) {
            $msg = "user id is required";
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        }

        $headers = $request->headers->all();
        $verify_request_base = Admin::verify_request_base($headers);
        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $ProfileCategoryData  = ProfileCategory::orderBy('profile_category_id', 'DESC')->get();

        if (count($ProfileCategoryData) > 0) {

            $data = [];
            $i = 0;
            foreach ($ProfileCategoryData as $value) {
                $data[$i]['profile_category_id'] = (int)$value['profile_category_id'];
                $data[$i]['profile_category_name'] = $value['profile_category_name'];
                $data[$i]['profile_category_image'] = $value['profile_category_image'] ? $value['profile_category_image'] : "";
                $i++;
            }

            return response()->json(['status' => 200, 'message' => "Profile Category Data Get Successfully.", 'data' => $data]);
        } else {
            return response()->json(['status' => 401, 'message' => "No Data Found."]);
        }
    }

    public function blockUser(Request $request)
    {

        $from_user_id = $request->user()->user_id;

        if (empty($from_user_id)) {
            $msg = "user id is required";
            return response()->json(['success_code' => 401, 'response_code' => 0, 'response_message' => $msg]);
        }

        $headers = $request->headers->all();
        $verify_request_base = Admin::verify_request_base($headers);

        if (isset($verify_request_base['status']) && $verify_request_base['status'] == 401) {
            return response()->json(['success_code' => 401, 'message' => "Unauthorized Access!"]);
            exit();
        }

        $rules = [
            'user_id' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $messages = $validator->errors()->all();
            $msg = $messages[0];
            return response()->json(['status' => 401, 'message' => $msg]);
        }

        $block_user_id = $request->get('user_id');

        $countBlockUser = BlockUser::where('from_user_id', $from_user_id)->where('block_user_id', $block_user_id)->count();

        if ($countBlockUser > 0) {

            $delete = BlockUser::where('from_user_id', $from_user_id)->where('block_user_id', $block_user_id)->delete();
            return response()->json(['status' => 200, 'message' => "User Unblock successful"]);
        } else {

            $data = array('block_user_id' => $block_user_id, 'from_user_id' => $from_user_id);
            $insert =  BlockUser::insert($data);

            return response()->json(['status' => 200, 'message' => "User Block successful."]);
        }
    }
}
