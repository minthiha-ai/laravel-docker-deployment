<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Township;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class TownshipController extends Controller
{
    use ResponseTrait;

    public function index()
    {
        return $this->success('Townships retrieved successfully', Township::orderBy('TS_Code', 'desc')->paginate(20));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'TS_Code' => 'required|string|unique:townships',
                'TS_Name' => 'required|string',
                'TS_Name_MMR' => 'required|string',
                'SR_Code' => 'required|string',
                'D_Code' => 'required|string',
                'active' => 'boolean',
            ]);

            $township = Township::create($validated);

            return $this->success('Township created successfully', $township, 201);
        } catch (ValidationException $e) {
            return $this->fail('Validation failed', 422, $e->errors());
        } catch (Throwable $e) {
            return $this->exception('An error occurred while creating the township', $e);
        }
    }

    public function show(Township $township)
    {
        return $this->success('Township retrieved successfully', $township);
    }

    public function update(Request $request, Township $township)
    {
        try {
            $validated = $request->validate([
                'TS_Code' => 'string|unique:townships,TS_Code,' . $township->id,
                'TS_Name' => 'string',
                'TS_Name_MMR' => 'string',
                'SR_Code' => 'string',
                'D_Code' => 'string',
                'active' => 'boolean',
            ]);

            $township->update($validated);

            return $this->success('Township updated successfully', $township);
        } catch (ValidationException $e) {
            return $this->fail('Validation failed', 422, $e->errors());
        } catch (Throwable $e) {
            return $this->exception('An error occurred while updating the township', $e);
        }
    }

    public function destroy(Township $township)
    {
        try {
            $township->delete();

            return $this->success('Township deleted successfully');
        } catch (Throwable $e) {
            return $this->exception('An error occurred while deleting the township', $e);
        }
    }
}
