<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Contrato;
use App\Models\Empleado;
use App\Models\Cargo;
use App\Models\EmpContrato;

class ImportController extends Controller
{
    public function procesarArchivo(Request $request)
    {
        $file = $request->file('archivo');

        // Cargar el archivo utilizando PhpSpreadsheet
        $spreadsheet = IOFactory::load($file);

        // Importar datos de la hoja CONTRATOS
        $contratosSheet = $spreadsheet->getSheetByName('CONTRATOS');
        $this->importSheet($contratosSheet, Contrato::class, ['ID', 'NOMBRE']);

        // Importar datos de la hoja EMPLEADOS
        $empleadosSheet = $spreadsheet->getSheetByName('EMPLEADOS');
        $this->importSheet($empleadosSheet, Empleado::class, ['ID', 'NOMBRE', 'TIPO']);

        // Importar datos de la hoja CARGOS
        $cargosSheet = $spreadsheet->getSheetByName('CARGOS');
        $this->importSheet($cargosSheet, Cargo::class, ['ID', 'NOMBRE']);

        // Importar datos de la hoja EMP_CONTRATO
        $empContratoSheet = $spreadsheet->getSheetByName('EMP_CONTRATO');
        $this->importSheet($empContratoSheet, EmpContrato::class, ['ID', 'ID_CONTRATO', 'TIPOEMPLEADO', 'ID_EMPLEADO', 'ID_CARGO']);

        // Redireccionar con un mensaje de éxito
        return redirect()->back()->with('message', 'Importación completada exitosamente.');
    }

    private function importSheet($sheet, $modelClass, $columns)
    {
        // Convertir la hoja a un arreglo
        $rows = $sheet->toArray();

        // Iterar sobre las filas y guardar en la base de datos
        foreach ($rows as $row) {
            $data = [];
            foreach ($columns as $index => $column) {
                $data[$column] = $row[$index] ?? null;
            }

            $modelClass::create($data);
        }
    }
}
