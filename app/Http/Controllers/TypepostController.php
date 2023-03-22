<?php

namespace App\Http\Controllers;
use App\Models\Typepost;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;


class TypepostController extends Controller
{
    public function topictype() {
        try {
            $data = Typepost::select('nametype as topictype', 'title')
                    ->where('type', 'topicType')
                    ->where('nametype', 'ALERT')
                    ->get();

            return response()->json([
                        'success' => true,
                        'message' => "Mise a jour traité avec succes",
                        'data' => $data,
                        'status' => Response::HTTP_OK,
                            ], Response::HTTP_OK);
        } catch (QueryException $ex) {
            return response()->json(
                            [
                                'success' => false,
                                'message' => $ex->getMessage(),
                                'status' => 400,
                            ],
                            400
            );
        }
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
     * @param  \App\Models\Typepost  $typepost
     * @return \Illuminate\Http\Response
     */
    public function show(Typepost $typepost)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Typepost  $typepost
     * @return \Illuminate\Http\Response
     */
    public function edit(Typepost $typepost)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Typepost  $typepost
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Typepost $typepost)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Typepost  $typepost
     * @return \Illuminate\Http\Response
     */
    public function destroy(Typepost $typepost)
    {
        //
    }
}
