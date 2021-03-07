<?php

namespace App\Http\Controllers;

use App\Models\Replie;
use App\Models\Thread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Fractalistic\Fractal;

class ThreadController extends Controller
{
    /**
     * ThreadController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    /**
     * Display a listing of the resource.
     *
     * @return string
     */
    public function index(): string
    {
        $allThreads = Thread::with('user')
        ->with('replie.user')
        ->with('replie.thread')
        ->with('channel')
        ->get();


        return Fractal::create()->collection($allThreads)
        ->transformWith(function($allThreads) {
            $user = (object)[
                "data" => $allThreads['user']
            ];

            $replies = (object)[
                "data" => $allThreads['replie']
            ];

            $channel = (object)[
                "data" => $allThreads['channel']
            ];

            return
            [
                'id' => $allThreads['id'],
                'title' => $allThreads['title'],
                'slug' => $allThreads['slug'],
                'body' => $allThreads['body'],
                'user' =>$user,
                'replies'=> $replies,
                'channel' => $channel
            ];
        })
        ->toJson();
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'body' => 'required',
            'channel_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 422);
        }

        $insertThread = new Thread();
        $insertThread->title = $request->title;
        $insertThread->slug = Str::slug($request->title);
        $insertThread->body = $request->body;
        $insertThread->channel_id = $request->channel_id;
        $insertThread->user_id = auth()->user()->id;
        $insertThread->save();

        $getThreadCreated = Thread::findOrFail($insertThread->id);

        return response()->json(['data'=> $getThreadCreated],201);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Thread $thread
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Thread $thread,$id): \Illuminate\Http\JsonResponse
    {
        $oneThread = Thread::with('user')
        ->with('replie.user')
        ->with('replie.thread')
        ->with('replie.thread.user')
        ->with('channel')
        ->where('id',$id)->get();

        if (count($oneThread) ===0 ){
            return response()->json(['errors'=>(object)[]],404);
        }

        $structOneThread = Fractal::create()->item($oneThread[0])
        ->transformWith(function($oneThread) {

            $structReplies = Fractal::create()->collection($oneThread['replie'])
            ->transformWith(function($oneThread) {
                return [
                    'id' => $oneThread['id'],
                    'created_at' => $oneThread['created_at'],
                    'updated_at' => $oneThread['updated_at'],
                    'body' => $oneThread['body'],
                    'user' => (object)[
                        "data" =>[
                            "name" => $oneThread['user']['name'],
                            "email" => $oneThread['user']['email'],
                        ]
                    ],
                    "thread" => (object)[
                        "data" =>[
                            "id" => $oneThread['thread']['id'],
                            "title" => $oneThread['thread']['title'],
                            "slug" => $oneThread['thread']['slug'],
                            "body" => $oneThread['thread']['body'],
                            "user" => $oneThread['thread']['user'],
                        ]
                    ]
                ];
            })
            ->toArray();

            $channel = (object)[
                "data" => $oneThread['channel']
            ];

            return [
                'data' => (object)[
                    'id' => $oneThread['id'],
                    'title' => $oneThread['title'],
                    'slug' => $oneThread['slug'],
                    'body' => $oneThread['body'],
                    'user' =>$oneThread['user'],
                ],
                'channel' => $channel,
                'replies'=> $structReplies
            ];
        })
        ->toArray();

        return response()->json($structOneThread['data']);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Thread  $thread
     * @return \Illuminate\Http\Response
     */
    public function edit(Thread $thread)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Thread  $thread
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Thread $thread)
    {

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Thread $thread
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Thread $thread,$id): \Illuminate\Http\JsonResponse
    {
        $existThread = Thread::where('id',$id)->get();

        $authorizeThread = Thread::where('id',$id)->where("user_id",auth()->user()->id)->get();

        if (count($existThread) !=0 && count($authorizeThread) !=0 ){
            Thread::where('id',$id)->delete();
            Replie::where('thread_id',$id)->delete();
            return response()->json([],204);
        }
        elseif (count($existThread) !=0 && count($authorizeThread) === 0 ){
            return response()->json(['errors'=>(object)[]],403);
        }
        else{
            return response()->json(['errors'=>(object)[]],404);
        }
    }
}
