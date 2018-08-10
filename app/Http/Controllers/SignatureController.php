<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

use App\Model\Signature;
use Validator;

class SignatureController extends Controller
{
    /**
     * Get all records
     * @return object
     */
    public function all(Request $request)
    {
        return response()->json(Signature::with('user')->paginate($request->get('limit')));
    }

    /**
     * Get a single record
     * @param  Request $request Request class
     * @param  integer  $id      User Integer
     * @return object
     */
    public function get(Request $request, Signature $signature)
    {
        return response()->json($signature);
    }

    /**
     * Adds a record to the database
     * @param Request $request Request Object
     * @return object
     */
    public function add(Request $request)
    {
        // type is required
        if(is_null($type = $request->get('type'))) {
            return response()->json([
                'message' => 'Type is required'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        //validation
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:draw,upload,text',
            'image' => 'required|mimes:jpeg,png',
            'font_id' => 'exists:fonts,id',
        ]);

        // font_id conditional
        $validator->sometimes(['font_id','text'], 'required', function ($input) {
            return $input->type == 'text';
        });

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()
            ], Response::HTTP_BAD_REQUEST);
        }

        // attempt to upload the signature
        if(!$image = $request->file('image')->store('signatures')) {
            return response()->json([
                'message' => "There was error saving the signature"
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // attempt to create the signature
        if(!$signature = Signature::create([
            'type' => $type,    
            'user_id' => $request->user()->id,
            'image' => basename($image),
            'font_id' => $request->get('font_id'),
            'text' => $request->get('text')
        ])) {
            return response()->json([
                'message' => "Signature could not be created"
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // the first signature is default
        if(is_null($request->user()->signature_id)) {
            $request->user()->signature_id = $signature->id;
            $request->user()->save();
        } 
        // success return
        return response()->json($signature);

    }

    /**
     * Removes or Soft Deletes the record
     * @param  Request $request Request Object
     * @param  integer  $id      ID of the record 
     * @return Object
     */
    public function remove(Request $request, Signature $signature)
    {
        // remove all relationships?

        // delete song
        $signature->delete();

        // return the response
        return response()->json([
            'message' => 'Signature has been deleted.'
        ], Response::HTTP_BAD_REQUEST);
    }

}