<?php

namespace App\Http\Controllers;

use App\Post;
use Illuminate\Http\Request;

class formpostController extends Controller
{
	public function getFormPost()
	{
		return view("formpost.storePost");
	}
	
	public function storePost(Request $request){
		return $request->get('id');
	}
}

?>