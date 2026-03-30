<?php

namespace App\Http\Controllers;


use App\Repositories\LocationRepository;
use App\Transformers\LocationTransformer;
use Illuminate\Http\Request;

class StateDistricMunController extends Controller
{
    protected $locationRepository;
    protected $locationTransformer;
    
    public function __construct(
        LocationRepository $locationRepository, 
        LocationTransformer $locationTransformer
    ){
        $this->locationRepository = $locationRepository;
        $this->locationTransformer = $locationTransformer;
    } 

    public function getAllStates()
    {
        $states = $this->locationRepository->getAllStates();
        return response()->json($this->locationTransformer->transformState($states));
    }

    public function getDistrictsByStateId(Request $request)
    {
        $stateId = $request->input('state_id');
        if (!$stateId) {
            return response()->json(['error' => 'State ID is required'], 400);
        }
        try {
             $districts = $this->locationRepository->getDistrictsByStateId($stateId);
        return response()->json($this->locationTransformer->transformDistrict($districts));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch districts: ' . $e->getMessage()], 500);
        }
       
    }

    public function getMunicipalitiesByDistrictId(Request $request)
    { $districtId = $request->input('district_id');
        
        if (!$districtId) {
            return response()->json(['error' => 'district_id is required'], 400);
        }

        try {
            $municipalities = $this->locationRepository->getMunicipalitiesByDistrictId($districtId);
            $formattedMunicipalities = $this->locationTransformer->transformMunicipality($municipalities);
            return response()->json(['municipalities' => $formattedMunicipalities], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch municipalities: ' . $e->getMessage()], 500);
        }
    }
}
