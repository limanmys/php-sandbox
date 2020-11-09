<?php

use Liman\Toolkit\Validator;

if (!function_exists('validate')) {
	function validate($rules)
	{
		$validator = (new Validator())->make(request(), $rules);
		if ($validator->fails()) {
			$errors = $validator->errors();
			abort($errors->first(), 400);
		}
	}
}
