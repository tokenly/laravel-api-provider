<?php

namespace Tokenly\LaravelApiProvider\Helpers;

use Exception;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Tokenly\LaravelApiProvider\Contracts\APIPermissionedUserContract;
use Tokenly\LaravelApiProvider\Contracts\APIResourceRepositoryContract;
use Tokenly\LaravelApiProvider\Contracts\APIUserContract;
use Tokenly\LaravelApiProvider\Filter\RequestFilter;
use Tokenly\LaravelApiProvider\Helpers\ResponseException;

class APIControllerHelper {

    public function __construct() {
        // code
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function transformResourcesForOutput($resources, $context=null, $wrapper_function=null)
    {
        $out = [];
        foreach ($resources as $resource) {
            $out[] = $resource->serializeForAPI($context);
        }

        if ($wrapper_function !== null) {
            $out = $wrapper_function($out);
        }

        return $this->buildJSONResponse($out);
    }

    public function transformResourceForOutput($resource, $context=null)
    {
        return $this->buildJSONResponse($resource->serializeForAPI($context));
    }

    public function transformValueForOutput($data) {
        return $this->buildJSONResponse($data);
    }

    public function buidPagedItemList($items, $page_offset, $per_page, $total_item_count) {
        return [
            'page'      => $page_offset,
            'perPage'   => $per_page,
            'pageCount' => ceil($total_item_count / $per_page),
            'count'     => $total_item_count,
            'items'     => $items,
        ];
    }

    public function buidPagedResourcesForOutput($resources, RequestFilter $filter, $context=null, $wrapper_function=null) {
        $serialized_resources = [];
        foreach ($resources as $resource) {
            $serialized_resources[] = $resource->serializeForAPI($context);
        }

        if ($wrapper_function !== null) {
            $serialized_resources = $wrapper_function($out);
        }

        return $this->buildJSONResponse($this->buidPagedItemList($serialized_resources, $filter->used_page_offset, $filter->used_limit, $filter->getCountForPagination()));
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

    public function requireResourceOwnedByUserOrWithPermssion($uuid, APIPermissionedUserContract $user, APIResourceRepositoryContract $repository, $permission) {
        if ($user->hasPermission($permission)) {
            return $this->requireResource($uuid, $repository);
        } else {
            return $this->requireResourceOwnedByUser($uuid, $user, $repository);
        }
    }

    public function requireResourceOwnedByUser($uuid, APIUserContract $user, APIResourceRepositoryContract $repository) {
        // lookup the resource
        $resource = $repository->findByUuid($uuid);

        // handle resource not found
        if (!$resource) { throw $this->buildJSONResponseException('Resource not found.', 404); }

        // validate that the resource belongs to the user
        if (!$user OR $resource['user_id'] != $user->getID()) { throw $this->buildJSONResponseException('Resource unauthorized.', 403); }

        return $resource;
        // $this->transformResourceForOutput($resource);
    }

    public function requireResource($uuid, APIResourceRepositoryContract $repository) {
        // lookup the resource
        $resource = $repository->findByUuid($uuid);

        // handle resource not found
        if (!$resource) { throw $this->buildJSONResponseException('Resource not found.', 404); }

        return $resource;
    }

    // this is to find users that belong to the current user
    //   but also allows the user to find themselves
    public function requireResourceIsUserOrIsOwnedByUser($uuid, APIUserContract $user, APIResourceRepositoryContract $repository) {
        if (strlen($uuid) AND $user->getUuid() == $uuid) { return $user; }

        return $this->requireResourceOwnedByUser($uuid, $user, $repository);
    }

    public function requirePermission(APIPermissionedUserContract $user, $permission, $description=null) {
        if (!$user->hasPermission($permission)) {
            throw $this->buildResponseException("This user is not authorized to ".($description ? $description : ' perform this action'), 403);
        }
    }

    public function buildJSONResponseException($error, $error_code=403) {
        return new HttpResponseException($this->newJsonResponseWithErrors($error, $error_code));
    }

    public function newJsonResponseWithErrors($errors, $code=500, $message=null) {
        if (is_array($errors)) {
            if ($message === null) {
                $message = implode(" ", $errors);
            }
        } else {
            if ($message === null) {
                $message = $errors;
            }
            $errors = [$errors];
        }
        return $this->buildJSONResponse(['message' => $message, 'errors' => $errors], $code);
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
        return $this->buildJSONResponse($out);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(APIResourceRepositoryContract $repository, $attributes)
    {
        $new_resource = $repository->create($attributes);
        return $this->buildJSONResponse($new_resource->serializeForAPI());
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
        if (!$resource) { return $this->buildJSONResponse(['message' => 'resource not found'], 404); }

        return $this->buildJSONResponse($resource->serializeForAPI());
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  string  $uuid
     * @return Response
     */
    public function update(APIResourceRepositoryContract $repository, $uuid, $attributes, APIUserContract $owner=null)
    {
        if ($owner !== null) {
            $resource = $this->requireResourceOwnedByUser($uuid, $owner, $repository);
        } else {
            $resource = $repository->findByUuid($uuid);
        }

        if (!$resource) { return $this->buildJSONResponse(['message' => 'resource not found'], 404); }

        $success = $repository->update($resource, $attributes);
        if (!$success) { return $this->buildJSONResponse(['message' => 'resource not found'], 404); }

        return $this->buildJSONResponse($resource->serializeForAPI());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy(APIResourceRepositoryContract $repository, $id, $required_user_id=null)
    {
        $resource = $repository->findByUuid($id);
        if (!$resource) { return $this->buildJSONResponse(['message' => 'resource not found'], 404); }

        if ($required_user_id !== null AND isset($resource['user_id'])) {
            if ($resource['user_id'] != $required_user_id) {
                throw $this->buildJSONResponseException('Not authorized to destroy this resource.', 403);;
            }
        }

        // delete
        $repository->delete($resource);

        // return 204
        return new Response('', 204);
    }

    public function buildJSONResponse($data, $http_code=200) {
        return new JsonResponse($data, $http_code);
    }

    // ------------------------------------------------------------------------
    // Ajax functions

    public function withAjax($callback) {
        try {
            $data = $callback();
            return $this->buildJSONResponse($data, 200);
        } catch (ResponseException $e) {
            return $this->newJsonResponseWithErrors($e->getErrors(), $e->getCode());
        } catch (HttpResponseException $e) {
            // HttpResponseException passes through
            throw $e;
        } catch (Exception $e) {
            Log::error("An error occurred: ".$e->getMessage(), ['exception' => $e,]);
            return $this->newJsonResponseWithErrors("An error occurred with this request", 500);
        }
    }


}
