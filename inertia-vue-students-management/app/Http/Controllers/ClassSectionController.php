<?php

namespace App\Http\Controllers;

use App\Repositories\ClassesRepository;
use App\Transformers\ClassesTransformer;
use App\Transformers\SectionTransformer;
use Illuminate\Http\Request;

class ClassSectionController extends Controller
{
    protected $classesRepository;
    protected $classesTransformer;
    protected $sectionTransformer;
    public function __construct
    (
            ClassesRepository $classesRepository,
            ClassesTransformer $classesTransformer,
            SectionTransformer $sectionTransformer
    )
    {
        $this->classesRepository = $classesRepository;
        $this->classesTransformer = $classesTransformer;
        $this->sectionTransformer = $sectionTransformer;
    }

    public function getAllClasses()
    {
        $classes = $this->classesRepository->getAllClasses();
        $transformedClasses = $this->classesTransformer->transformClasses($classes);
        // [ $formattedMunicipalities]
        return response()->json(['classesList' =>  $transformedClasses]);
    }

    public function getAllSection()
    {
        $classes = $this->classesRepository->getAllSections();
        $transformedClasses = $this->sectionTransformer->transformSection($classes);
        // [ $formattedMunicipalities]
        return response()->json(['sectionList' =>  $transformedClasses]);
    }
}
