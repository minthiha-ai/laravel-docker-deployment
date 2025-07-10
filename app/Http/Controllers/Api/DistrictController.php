<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Township;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class DistrictController extends Controller
{
    use ResponseTrait;

    public function index()
    {
        return $this->success('Districts retrieved successfully', District::orderBy('D_Code', 'desc')->paginate(20));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'D_Code' => 'required|string|unique:districts',
                'D_Name' => 'required|string',
                'D_Name_MMR' => 'required|string',
                'SR_Code' => 'required|string',
                'active' => 'boolean',
            ]);

            $district = District::create($validated);

            return $this->success('District created successfully', $district, 201);
        } catch (ValidationException $e) {
            return $this->fail('Validation failed', 422, $e->errors());
        } catch (Throwable $e) {
            return $this->exception('An error occurred while creating the district', $e);
        }
    }

    public function show(District $district)
    {
        return $this->success('District retrieved successfully', $district->load('townships'));
    }

    public function update(Request $request, District $district)
    {
        try {
            $validated = $request->validate([
                'D_Code' => 'string|unique:districts,D_Code,' . $district->id,
                'D_Name' => 'string',
                'D_Name_MMR' => 'string',
                'SR_Code' => 'string',
                'active' => 'boolean',
            ]);

            $district->update($validated);

            return $this->success('District updated successfully', $district);
        } catch (ValidationException $e) {
            return $this->fail('Validation failed', 422, $e->errors());
        } catch (Throwable $e) {
            return $this->exception('An error occurred while updating the district', $e);
        }
    }

    public function destroy(District $district)
    {
        try {
            // Manually delete related townships
            Township::where('D_Code', $district->D_Code)->delete();

            $district->delete(); // Delete district

            return $this->success('District and its related townships deleted successfully');
        } catch (Throwable $e) {
            return $this->exception('An error occurred while deleting the district', $e);
        }
    }
}
