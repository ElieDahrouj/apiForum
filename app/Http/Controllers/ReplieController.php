<?php

namespace App\Http\Controllers;

use App\Models\Replie;
use App\Models\Thread;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Fractalistic\Fractal;

class ReplieController extends Controller
{
    /**
     * ReplieController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @param $dataCollection
     */
    public function displayDataReplies($dataCollection): array
    {
        return Fractal::create()->item($dataCollection[0])
        ->transformWith(function($getRepliesCreated) {
            return [
                'id' => $getRepliesCreated['id'],
                'created_at' => $getRepliesCreated['created_at'],
                'updated_at' => $getRepliesCreated['updated_at'],
                'body' => $getRepliesCreated['body'],
                'user' => (object)[
                    "data" => (object)[
                        "name" => $getRepliesCreated['user']['name'],
                        "email" => $getRepliesCreated['user']['email']
                    ]
                ],
                "thread" => (object)[
                    "data" =>(object)[
                        'id' => $getRepliesCreated['thread']['id'],
                        'title' => $getRepliesCreated['thread']['title'],
                        'slug' => $getRepliesCreated['thread']['slug'],
                        'body' => $getRepliesCreated['thread']['body'],
                        'user' => (object)[
                            "name" => $getRepliesCreated['thread']['user']['name'],
                            "email" => $getRepliesCreated['thread']['user']['email']
                        ],
                    ]
                ]
            ];
        })
        ->toArray();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
     * @param \Illuminate\Http\Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request,$id): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'body' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(["errors" => $validator->errors()], 422);
        }

        $getOneThread = Thread::where('id',$id)->get();

        if (count($getOneThread) === 0){
            return response()->json(['errors'=>(object)[]],404);
        }

        $insertReplies = new Replie();
        $insertReplies->body = $request->body;
        $insertReplies->thread_id = $id;
        $insertReplies->user_id = auth()->user()->id;
        $insertReplies->save();

        $getRepliesCreated = Replie::with('user')
        ->with('thread')
        ->with('thread.user')
        ->where("id",$insertReplies->id)
        ->get();

        $structRepliesToThreads = $this->displayDataReplies($getRepliesCreated);

        return response()->json(['data'=> $structRepliesToThreads['data']],201);
    }

    /**
     * Display the specified resource.
     *
     * @param Replie $replie
     * @param $id
     * @param $reply
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Replie $replie,$id,$reply): \Illuminate\Http\JsonResponse
    {
        $getOneReplies =  Replie::with('user')
            ->with('thread')
            ->with('thread.user')
            ->where([
                ['thread_id', '=', $id],
                ['id', '=', $reply],
            ])
        ->get();

        if (count($getOneReplies) === 0){
            return response()->json(['errors'=>(object)[]],404);
        }

        if ($getOneReplies[0]['user_id'] != auth()->user()->id){
            return response()->json(['errors'=>(object)[]],403);
        }

        $showOneRepliesToThreads = $this->displayDataReplies($getOneReplies);

        return response()->json(['data'=> $showOneRepliesToThreads['data']]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Replie  $replie
     * @return \Illuminate\Http\Response
     */
    public function edit(Replie $replie)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Replie  $replie
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Replie $replie)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Replie $replie
     * @param $id
     * @param $reply
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Replie $replie,$id,$reply): \Illuminate\Http\JsonResponse
    {
        $getOneRepliesToDestroy =  Replie::with('user')
        ->with('thread')
        ->with('thread.user')
        ->where([
            ['thread_id', '=', $id],
            ['id', '=', $reply],
        ])
        ->get();

        if (count($getOneRepliesToDestroy) === 0){
            return response()->json(['errors'=>(object)[]],404);
        }

        if ($getOneRepliesToDestroy[0]['user_id'] != auth()->user()->id){
            return response()->json(['errors'=>(object)[]],403);
        }

        Replie::where([
            ['thread_id', '=', $id],
            ['id', '=', $reply],
        ])->delete();
        return response()->json([],204);

    }
}
