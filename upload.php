<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date;

if ($_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
    // Define allowed Excel file types
    $allowedExcelExtensions = ['xls', 'xlsx'];

    if ($_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['code' => 102, 'message' => 'Failed to upload the file. Please refresh your page and try again.']);
        return;
    }

    $fileExtension = pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION);

    // Validate Excel file type
    if (!in_array($fileExtension, $allowedExcelExtensions)) {
        echo json_encode(['code' => 100, 'message' => 'Invalid file type. Only .xls and .xlsx files are allowed.']);
        return;
    }

    // Load the Excel file without saving it
    try {
        $spreadsheet = IOFactory::load($_FILES['excel_file']['tmp_name']);
        $worksheet = $spreadsheet->getActiveSheet();
    } catch (Exception $e) {
        echo json_encode(['code' => 101, 'message' => 'Failed to load Excel file. It may be corrupted.']);
        return;
    }

    $highestRow = $worksheet->getHighestRow();
    if ($highestRow < 2) {
        echo json_encode(['code' => 102, 'message' => 'No data found in the Excel file.']);
        return;
    }

    // Define expected columns
    $expectedColumns = [
        'first_name',
        'second_name',
        'last_name',
        'email',
        'telephone',
        'passport',
        'nationality',
        'occupation',
        'birth_place',
        'birth_date',
        'relationship',
        'gender',
        'residence'
    ];

    // Validate headers
    $headers = $worksheet->rangeToArray('A1:M1', null, true, true, true)[1];
    $actualColumns = array_values($headers);

    if ($actualColumns !== $expectedColumns) {
        echo json_encode(['code' => 100, 'message' => 'The Excel file does not have the required columns in the correct order.']);
        return;
    }

    // Get splashData from POST request
    $splashData = json_decode($_POST['splashData'], true);
    $allowedGenders = array_column($splashData['gender'], 'gender', 'row_id');
    $allowedRelationships = array_column($splashData['relationship_types'], 'relationship_type', 'row_id');
    $allowedNationalities = array_column($splashData['nationality'], 'nationality', 'row_id');
    $allowedResidences = array_column($splashData['countries'], 'country', 'row_id');

    // Collect data from the Excel file
    $data = [];
    $errors = [];

    foreach ($worksheet->getRowIterator(2) as $row) {
        $rowData = [];
        $rowErrors = [];
        foreach ($expectedColumns as $index => $header) {
            $col = chr(65 + $index); // Convert to column letters (A, B, C, etc.)
            $cellValue = $worksheet->getCell($col . $row->getRowIndex())->getValue();

            // Format birth_date as "yyyy-mm-dd"
            if ($header === 'birth_date' && is_numeric($cellValue)) {
                $cellValue = Date::excelToDateTimeObject($cellValue)->format('Y-m-d');
            }

            // Validate gender
            if ($header === 'gender') {
                $genderLower = ucfirst(strtolower(trim($cellValue)));
                if (!in_array($genderLower, $allowedGenders)) {
                    $rowErrors['gender'] = $cellValue;
                } else {
                    $cellValue = array_search($genderLower, $allowedGenders); // Set to row_id
                }
            }

            // Validate nationality
            if ($header === 'nationality') {
                $nationalityLower = ucfirst(strtolower(trim($cellValue)));
                if (!in_array($nationalityLower, $allowedNationalities)) {
                    $rowErrors['nationality'] = $cellValue;
                } else {
                    $cellValue = array_search($nationalityLower, $allowedNationalities); // Set to row_id
                }
            }

            // Validate residence
            if ($header === 'residence') {
                $residenceLower = ucfirst(strtolower(trim($cellValue)));
                if (!in_array($residenceLower, $allowedResidences)) {
                    $rowErrors['residence'] = $cellValue;
                } else {
                    $cellValue = array_search($residenceLower, $allowedResidences); // Set to row_id
                }
            }

            // Validate relationship type
            if ($header === 'relationship') {

                $relationshipLower = ucfirst(strtolower(trim($cellValue)));
                if (!in_array($relationshipLower, $allowedRelationships)) {
                    $relationshipLower = 'Others'; // Default to "Other"
                    $cellValue = array_search($relationshipLower, $allowedRelationships); // Set to row_id
                } else {
                    $cellValue = array_search($relationshipLower, $allowedRelationships); // Set to row_id
                }
            }

            // Preserve the formatted telephone field
            if ($header === 'telephone') {
                $cellValue = $worksheet->getCell($col . $row->getRowIndex())->getFormattedValue();
            }

            $rowData[$header] = $cellValue;
        }

        if (!empty($rowErrors)) {
            $errors[] = ['row' => $row->getRowIndex(), 'errors' => $rowErrors];
        }

        $data[] = $rowData;
    }
    // Validate that the number of people details is not less than 9
    if (count($data) < 9) {
        echo json_encode(['code' => 104, 'message' => 'The number of people details should not be less than 9.']);
        return;
    }

    // After validating all rows, handle errors
    if (!empty($errors)) {
        $uniqueErrors = []; // To hold unique errors

        // Loop through the errors to collect unique key-value pairs
        foreach ($errors as $rowErrors) {
            foreach ($rowErrors['errors'] as $key => $value) {
                $uniqueErrors["$key: \"$value\""] = true; // Use the key-value as a unique identifier
            }
        }

        // Format the unique errors into a string
        $errorMessageString = implode(', ', array_keys($uniqueErrors));

        // Return the JSON response with the formatted error message
        echo json_encode([
            'code' => 106,
            'message' => "Failed to process data: [$errorMessageString]"
        ]);
        return;
    }

    // Return processed data as JSON
    header('Content-Type: application/json');
    echo json_encode(['code' => 200, 'message' => "Data processed successfully", 'data' => $data]);
}
