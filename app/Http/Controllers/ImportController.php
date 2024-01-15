<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use App\Models\Contrato;
use App\Models\User;
use App\Models\Empleado;
use App\Models\EmpleadoCargo;
use App\Models\Avance;


class ImportController extends Controller
{
    public function procesarArchivo(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,csv|max:102400',
        ]);
        $file = $request->file('archivo');
        try {
            $spreadsheet = $this->cargarArchivo($file);
            $hojasImportadas = 0;
            // Importar datos de la hoja USUARIOS
            $hojasImportadas += $this->importarHoja($spreadSheet, 'usuarios', 'App\Models\User', [
                'name', 'email'
            ]);
            // Importar datos de la hoja CONTRATOS
            $hojasImportadas += $this->importarHoja($spreadsheet, 'contratos', 'App\Models\Contrato', [
                'contrato', 'nombre_obra', 'descripcion',
                'fecha_alta', 'ubicacion', 'fecha_inicio',
                'fecha_termino', 'plazo_dias', 'importe',
                'amortizacion', 'estatus', 'id_cliente', 'id_empresa', 'id_responsable', 'id_asistente'
            ]);
            // Importar datos de la hoja UNIDADES
            $hojasImportadas += $this->importarHoja($spreadsheet, 'unidades', 'App\Models\Unidad', ['nombre', 'descripcion']);
            // Importar datos de la hoja CARGOS
            $hojasImportadas += $this->importarHoja($spreadsheet, 'cargos', 'App\Models\EmpleadoCargo', ['nombre', 'descripcion']);
            // Importar datos de la hoja CLIENTES
            $hojasImportadas += $this->importarHoja($spreadsheet, 'clientes', 'App\Models\Cliente', ['nombre', 'telefono', 'email']);
            // Importar datos de la hoja AFIANZADORAS
            $hojasImportadas += $this->importarHoja($spreadsheet, 'afianzadoras', 'App\Models\Afianzadora', ['nombre', 'rfc', 'razon_social', 'domicilio', 'telefono']);
            if ($hojasImportadas > 0) {
                return redirect()->back()->with('success', "Importación completada exitosamente. Se importaron $hojasImportadas hojas.");
            } else {
                return redirect()->back()->with('error', 'No se importaron datos. Verifica el formato del archivo y las hojas.');
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }
    }
    protected function cargarArchivo($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if ($extension == 'csv') {
            return $this->cargarCsv($file);
        } else {
            return IOFactory::load($file);
        }
    }
    protected function cargarCsv($file)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        if (($handle = fopen($file->getPathname(), 'r')) !== false) {
            $row = 1;
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $col = 1;
                foreach ($data as $cell) {
                    $sheet->setCellValueByColumnAndRow($col, $row, $cell);
                    $col++;
                }
                $row++;
            }
            fclose($handle);
        }
        return $spreadsheet;
    }
    protected function importarHoja($spreadsheet, $sheetName, $modelClass, $columnMapping)
    {
        if ($spreadsheet->sheetNameExists($sheetName)) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            $dataArray = $sheet->toArray();    
            if (count($dataArray) > 0) {
                $headerRow = array_shift($dataArray);
                $columnIndices = $headerRow ? array_flip($headerRow) : null;
                foreach ($dataArray as $i => $rowData) {
                    $modelData = $this->mapearColumnas($rowData, $columnMapping, $columnIndices);
                    try {
                        $this->ajustarFormatoDatos($modelData);
                        $this->crearRegistro($modelClass, $modelData);
                        \Log::info("Datos importados correctamente en la hoja '$sheetName', fila $i");
                    } catch (\Exception $e) {
                        \Log::error("Error al importar datos en la hoja '$sheetName', fila $i: " . $e->getMessage());
                    }
                }    
                return 1; // Indica que la hoja fue importada exitosamente
            }
        }
        return 0; // Indica que la hoja no existe o no fue importada
    }
    protected function ajustarFormatoDatos(&$modelData)
    {
        foreach ($modelData as $key => &$value) {
            // Ajustar formato de fechas
            if (in_array($key, ['fecha_alta', 'fecha_inicio', 'fecha_termino'])) {
                $value = $this->ajustarFormatoFecha($value);
            }
            // Ajustar formato de datos numéricos
            if ($this->esCampoNumerico($value) && !str_contains($key, 'telefono')) {
                $value = (float) $value; // Convertir a número decimal
            }
        }
    }
    protected function ajustarFormatoFecha($date)
    {
        try {
            return \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date));
        } catch (\Exception $e) {
            return $date; // Si no puede convertirse, devuelve el valor original
        }
    }
    protected function esCampoNumerico($value)
    {
        return is_numeric($value);
    }
    protected function mapearColumnas($rowData, $columnMapping, $columnIndices)
    {
        $mappedData = [];
        foreach ($columnMapping as $modelKey => $sheetColumn) {
            if (is_numeric($modelKey)) {
                // No se proporcionó una clave, usar el nombre de la columna como clave
                $modelKey = $sheetColumn;
            }
            // Asegurarse de que la columna existe en la fila antes de mapear
            if (array_key_exists($sheetColumn, $columnIndices)) {
                $mappedData[$modelKey] = $rowData[$columnIndices[$sheetColumn]];
            }
        }
        return $mappedData;
    }
    protected function crearRegistro($modelClass, $modelData)
    {
        // Crear un nuevo modelo con los datos de la fila
        $model = new $modelClass($modelData);
        $model->save();
    }
    
//Inicia codigo de importación de columnas

    public function procesarAvances(Request $request)
    {
        try {
            $archivo = $request->file('file');
            $avanceId = $request->input('avance_id');
            // Obtener la extensión del archivo
            $extension = $archivo->getClientOriginalExtension();
            // Validar y procesar el archivo usando PHPSpreadsheet
            $data = [];
            if ($extension === 'csv') {
                $reader = IOFactory::createReader('Csv');
                $reader->setDelimiter(',');
                $spreadsheet = $reader->load($archivo->getRealPath());
                $data = $spreadsheet->getActiveSheet()->toArray();
            } elseif ($extension === 'xlsx') {
                $data = IOFactory::load($archivo->getRealPath())->getActiveSheet()->toArray();
            } else {
                // Manejar otros formatos o mostrar un error si es necesario
                return redirect()->back()->with('error', 'Formato de archivo no admitido. Utiliza CSV o XLSX.');
            }
            // Omitir la primera fila si es un encabezado
            array_shift($data);
            // Iterar sobre las filas del archivo y guardar en la base de datos
            foreach ($data as $row) {
                $inicio = Carbon::createFromFormat('d/m/Y', $row[1], 'UTC');
                $fin = Carbon::createFromFormat('d/m/Y', $row[2], 'UTC');
                if ($inicio === false || $fin === false) {
                    // Si alguna fecha no es válida, manejar el error
                    return redirect()->back()->with('error', 'Error al procesar el archivo: Fecha no válida o formato incorrecto');
                }
                Avance::create([
                    'localizacion' => $row[0],
                    'inicio' => $inicio->format('Y-m-d'),
                    'fin' => $fin->format('Y-m-d'),
                    'altura' => $row[3],
                    'anchoP' => $row[4],
                    'anchoM' => $row[5],
                    'volumenT' => $row[6],
                    'pieza' => $row[7],
                    'espesor' => $row[8],
                    'area' => $row[9],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            return redirect()->back()->with('success', 'Datos importados exitosamente');
        } catch (\Exception $e) {
            // Manejo de excepciones
            return redirect()->back()->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }
    }
    
//

}
