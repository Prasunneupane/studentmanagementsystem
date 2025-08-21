<?php

namespace App\Http\Controllers;

use App\Repositories\ClassesRepository;
use App\Transformers\ClassesTransformer;
use Illuminate\Http\Request;

class ClassSectionController extends Controller
{
    protected $classesRepository;
    protected $classesTransformer;
    public function __construct
    (
            ClassesRepository $classesRepository,
            ClassesTransformer $classesTransformer
    )
    {
        $this->classesRepository = $classesRepository;
        $this->classesTransformer = $classesTransformer;
    }

    public function getAllClasses()
    {
        $classes = $this->classesRepository->getAllClasses();
        $transformedClasses = $this->classesTransformer->transformClasses($classes);
        
        return response()->json($transformedClasses);
    }
}
