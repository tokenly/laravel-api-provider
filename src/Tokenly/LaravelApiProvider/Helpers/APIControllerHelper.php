<?php

namespace Tokenly\LaravelApiProvider\Helpers;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Tokenly\LaravelApiProvider\Contracts\APIUserContract;
use Tokenly\LaravelApiProvider\Contracts\APIResourceRepositoryContract;

class APIControllerHelper {

    public function __construct() {
        // code
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function transformResourcesForOutput($resources)
    {
        $out = [];
        foreach ($resources as $resource) {
            $out[] = $resource->serializeForAPI();
        }
        return json_encode($out);
    }

    public function transformResourceForOutput($resource)
    {
        return json_encode($resource->serializeForAPI());
    }

    public function transformValueForOutput($data) {
        return json_encode($data);
    }

    public function getAttributesFromRequest(Request $request) {
        $attributes = [];
        $allowed_vars = array_keys($request->rules());
        $request_vars = $request->all();
        foreach($allowed_vars as $allowed_var_name) {
            if (isset($request_vars[$allowed_var_name])) {
                $attributes[$allowed_var_name] = $request_vars[$allowed_var_name];
            }
        }
        return $attributes;
    }

    public function requireResourceOwnedByUser($uuid, APIUserContract $user, APIResourceRepositoryContract $repository) {
        // lookup the resource
        $resource = $repository->findByUuid($uuid);

        // handle resource not found
        if (!$resource) { throw new HttpResponseException(new JsonResponse(['errors' => ['Resource not found.']], 404)); }

        // validate that the resource belongs to the user
        if (!$user OR $resource['user_id'] != $user->getID()) { throw new HttpResponseException(new JsonResponse(['errors' => ['Resource unauthorized.']], 403)); }

        return $resource;
        // $this->transformResourceForOutput($resource);
    }

    // this is to find users that belong to the current user
    //   but also allows the user to find themselves
    public function requireResourceIsUserOrIsOwnedByUser($uuid, APIUserContract $user, APIResourceRepositoryContract $repository) {
        if (strlen($uuid) AND $user->getUuid() == $uuid) { return $user; }

        return $this->requireResourceOwnedByUser($uuid, $user, $repository);
    }

    public function newJsonResponseWithErrors($errors, $code=500) {
        if (is_array($errors)) {
            $message = implode(" ", $errors);
        } else {
            $message = $errors;
            $errors = [$errors];
        }
        return new JsonResponse(['message' => $message, 'errors' => $errors], $code);
    }



    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(APIResourceRepositoryContract $repository)
    {
        // all monitors
        $out = [];
        foreach ($repository->findAll() as $resource) {
            $out[] = $resource->serializeForAPI();
        }
        return json_encode($out);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(APIResourceRepositoryContract $repository, $attributes)
    {
        $new_resource = $repository->create($attributes);
        return json_encode($new_resource->serializeForAPI());
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show(APIResourceRepositoryContract $repository, $id)
    {
        $resource = $repository->findByUuid($id);
        if (!$resource) { return new JsonResponse(['message' => 'resource not found'], 404); }

        return json_encode($resource->serializeForAPI());
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(APIResourceRepositoryContract $repository, $id, $attributes)
    {
        $resource = $repository->findByUuid($id);
        if (!$resource) { return new JsonResponse(['message' => 'resource not found'], 404); }

        $success = $repository->update($resource, $attributes);
        if (!$success) { return new JsonResponse(['message' => 'resource not found'], 404); }

        return json_encode($resource->serializeForAPI());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy(APIResourceRepositoryContract $repository, $id)
    {
        $resource = $repository->findByUuid($id);
        if (!$resource) { return new JsonResponse(['message' => 'resource not found'], 404); }

        // delete
        $repository->delete($resource);

        // return 204
        return new Response('', 204);
    }

}
