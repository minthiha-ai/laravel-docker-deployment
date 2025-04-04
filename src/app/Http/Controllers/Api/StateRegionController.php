<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StateRegion;
use App\Models\District;
use App\Models\Township;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class StateRegionController extends Controller
{
    use ResponseTrait;

    public function index()
    {
        return $this->success('State regions retrieved successfully', StateRegion::orderBy('SR_Code', 'desc')->paginate(20));
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'SR_Code' => 'required|string|unique:state_regions',
                'SR_Name' => 'required|string',
                'SR_Name_MMR' => 'required|string',
                'active' => 'boolean',
            ]);

            $stateRegion = StateRegion::create($validated);

            return $this->success('State region created successfully', $stateRegion, 201);
        } catch (ValidationException $e) {
            return $this->fail('Validation failed', 422, $e->errors());
        } catch (Throwable $e) {
            return $this->exception('An error occurred while creating the state region', $e);
        }
    }

    public function show(StateRegion $stateRegion)
    {
        return $this->success('State region retrieved successfully', $stateRegion);
    }

    public function update(Request $request, StateRegion $stateRegion)
    {
        try {
            $validated = $request->validate([
                'SR_Code' => 'string|unique:state_regions,SR_Code,' . $stateRegion->id,
                'SR_Name' => 'string',
                'SR_Name_MMR' => 'string',
                'active' => 'boolean',
            ]);

            $stateRegion->update($validated);

            return $this->success('State region updated successfully', $stateRegion);
        } catch (ValidationException $e) {
            return $this->fail('Validation failed', 422, $e->errors());
        } catch (Throwable $e) {
            return $this->exception('An error occurred while updating the state region', $e);
        }
    }

    public function destroy(StateRegion $stateRegion)
    {
        try {
            $districts = District::where('SR_Code', $stateRegion->SR_Code)->get();
            foreach ($districts as $district) {
                Township::where('D_Code', $district->D_Code)->delete();
                $district->delete();
            }

            $stateRegion->delete();

            return $this->success('State region and its related districts and townships deleted successfully');
        } catch (Throwable $e) {
            return $this->exception('An error occurred while deleting the state region', $e);
        }
    }
}
