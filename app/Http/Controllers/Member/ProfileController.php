<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Approbate;
use App\Models\Portfolio;
use App\Models\Position;
use App\Models\Member;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // $portfolio=auth()->user()->member->portfolio;
        // if(empty($portfolio)){
        //     $portfolio=new Portfolio;
        //     $portfolio->member_id=auth()->user()->member->id;
        //     $portfolio->approbate_id=1;
        //     $portfolio->save();
        // }
        $member = auth()->user()->member;
        if ($member == null) {
            return redirect()->back();
        };
        $member->positions;
        $member->athlete;
        $member->user;
        return Inertia::render('Member/Profile', [
            'member' => $member,
            // 'profile'=>$portfolio,
            'positions' => Position::all()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {}

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = $request->all();
        $member = Member::find($id);
        //$data['positions']=$request->positions;
        $member->update($data);

        if ($request->file('avatar')) {
            if ($member->avatar != null) {
                if (Storage::exists($member->avatar)) {
                    Storage::delete($member->avatar);
                }
            }
            $file = $request->file('avatar');
            $path = Storage::putFile('public/images/members/avatar', $file);
            $member->avatar = $path;
            $member->save();
        }

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    function sendSms(Member $member, Request $request)
    {
        $sms_company = env('SMS_COMPANY');
        $sms_username = env('SMS_UID');
        $sms_password = env('SMS_PWD');
        $randomNumber = rand(100000, 999999);
        // $data = $request->all();
        $user = $member->user;
        // dd($randomNumber, $user, $request->all());

        $user->mobile_verified_code = $randomNumber;
        $user->mobile = $request->confirm_mobile;
        $user->save();


        // dd($request->confirm_mobile);
        $recipient_xml = '<recipient>' . $request->confirm_mobile . '</recipient>';
        $content = '此信息為澳門柔道會員系統的電話驗證短信,你的驗證碼為:' . $randomNumber;
        $xml = <<<DATA
        <?xml version='1.0' encoding='UTF-8'?>
        <!DOCTYPE jds SYSTEM '/home/httpd/html/dtd/jds2.dtd'>
        <jds>
        <account acid='{$sms_company}' loginid='{$sms_username}' passwd='{$sms_password}'>
        <msg_send>
        <ref>Reference ID</ref> 
        {$recipient_xml}
        <content>{$content}</content>
        <language>C</language>
        </msg_send>
        </account>
        </jds>
        DATA;

        $url = "https://mlpsmsapi.three.com.mo/v1/externalApi/message";
        // $options = [
        //     'headers' => [
        //         'Content-Type' => 'application/xml',
        //         'Accept' => 'application/xml',
        //     ],
        //     'body' => $xml
        // ];

        // $client = new Client();

        // $response = $client->request('POST', $url, $options);
        // dd($response);
        // return $response->getBody();
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $headers = array(
            "Content-Type: application/xml",
            "Accept: application/xml",
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $resp = curl_exec($curl);
        curl_close($curl);
        $xml = simplexml_load_string($resp);

        return response()->json($xml);
    }
    public function confirmMobile(Member $member, Request $request)
    {
        $user = $member->user;


        $response = [
            'message' => 'Your custom message here',
        ];

        // 返回 JSON 格式的消息

        if ($user->mobile == $request->confirm_mobile && $user->mobile_verified_code == $request->verification_code) {
            $user->mobile_verified_at = now();
            $user->save();

            $response = [
                'message' => 'verified mobile success',
            ];
        } else {
            $response = [
                'message' => 'verified mobile error',
            ];
        }
        return redirect()->back()->with('message', 'verified mobile success');
    }
}
