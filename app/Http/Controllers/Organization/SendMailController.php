<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Mail\TestMail;
use Illuminate\Support\Facades\Mail;

class SendMailController extends Controller
{
    public function sendMailWithAttachment(Request $request)
    {
        // Laravel 8
        // $data["email"] = "test@gmail.com";
        // $data["title"] = "Techsolutionstuff";
        // $data["body"] = "This is test mail with attachment";

        // $files = [
        //     public_path('attachments/test_image.jpeg'),
        //     public_path('attachments/test_pdf.pdf'),
        // ];

        // Mail::send('mail.test_mail', $data, function($message)use($data, $files) {
        //     $message->to($data["email"])
        //             ->subject($data["title"]);

        //     foreach ($files as $file){
        //         $message->attach($file);
        //     }            
        // });
        $mailData = [
            'title' => 'This is Test Mail',
            'subject' => 'Test Mail',
            'view_name' => 'mail.testMail',
        ];

        Mail::to('abc95175346@hotmail.com')->send(new TestMail($mailData));

        echo "Mail send successfully !!";
    }
    public function sendApplicationMailWithAttachment(Request $request)
    {
        $mailData = [
            'title' => '恭喜你已成功報名',
            'subject' => '比賽報名表',
            'view_name' => 'mail.applicationMail',
        ];

        Mail::to($request->mail)->send(new TestMail($mailData));

        return redirect()->back();
    }
}
