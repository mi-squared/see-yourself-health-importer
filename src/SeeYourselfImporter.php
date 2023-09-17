<?php


namespace Mi2\SeeYourselfImport;

use Mi2\Import\Interfaces\ImporterServiceInterface;
use Mi2\Import\Interfaces\ColumnMapperInterface;
use Mi2\Import\Models\Batch;
use Mi2\Import\Models\Logger;
use Mi2\Import\Models\Response;
use Mi2\Import\Traits\InteractsWithCSVTrait;
use Mi2\Import\Traits\InteractsWithLists;
use Mi2\Import\Traits\InteractsWithLogger;
use OpenEMR\Services\PatientService;

class SeeYourselfImporter implements ImporterServiceInterface, ColumnMapperInterface
{
    use InteractsWithLogger, InteractsWithCSVTrait, InteractsWithLists;

    protected $count = 0;
    protected $patientService;
    protected static $column_mapping = [
        "First" => "fname",
        "Last" => "lname",
        "DOB" => "DOB",
        "Sex" => "sex",
        "Phone" => "phone_cell",
        "Email" => "email",
    ];

    public function __construct()
    {
        $this->patientService = new PatientService();
    }


    /**
     * To use the trait InteractsWithCSV, have to implement this
     */
    public function getColumnMapper()
    {
        return $this;
    }

    public function get_column_mapping()
    {
        return self::$column_mapping;
    }

    public function get_db_field($column_header_name)
    {
        return self::$column_mapping[$column_header_name];
    }

    public function import_row($csv_row)
    {
        if ($csv_row['First'] == '' && $csv_row['Last'] == '' && $csv_row['DOB'] == '') {
            return new Response("Missing one of required fields, (First Name, Last Name, DOB) assuming empty row");
        }
        $patient_data = $this->buildPatientDataTable($csv_row);
        $response = $this->importPatientData($patient_data);
        $this->count++;

        return $response;
    }

    //This returns the demographic information for a single patient.
    protected function buildPatientDataTable($patient_data)
    {
        $mapped_data = [];
        foreach ($patient_data as $spreadsheet_column_name => $value) {
            // Get the database column name for this column in the spreadsheet
            $mapped_key = self::$column_mapping[$spreadsheet_column_name];
            if ($mapped_key !== null) {
                // if the mapped key is null, we don't care about it.
                $mapped_data[$mapped_key] = $value;
            }
        }

        // For context in error messages, get the patient name
        $patient_name = $mapped_data['fname'] . " " . $mapped_data['lname'];

        // After the initial mapping, we need to do some additional formatting
        $mapped_data['DOB'] = date("Y-m-d", strtotime($mapped_data['DOB']));

        if ($mapped_data['sex'] == "M" || strtolower($mapped_data['sex']) == "male") {
            $mapped_data['sex'] = "Male";
        } else if ($mapped_data['sex'] == "F" || strtolower($mapped_data['sex']) == "memale") {
            $mapped_data['sex'] = "Female";
        } else {
            $mapped_data['sex'] = '';
        }

        // handle the city and state
        $city = explode(' ', $mapped_data['city']);
        $mapped_data['state'] = array_pop($city);
        $city = implode(" ", $city);
        $mapped_data['city'] = $city;

        $guardian_array = explode(' ', $mapped_data['guardiansname']);
        $mapped_data['guardianrelationship'] = array_pop($guardian_array);
        $name = implode(" ", $guardian_array);
        $mapped_data['guardiansname'] = $name;

        switch ($mapped_data['ethnicity']) {
            case "H":
                $mapped_data['ethnicity'] = 'hisp_or_latin';
                break;
            case "B" or "W":
                $mapped_data['ethnicity'] = 'not_hisp_or_latin';
            default:
                $mapped_data['ethnicity'] = '';
        }

        // $mapped_data['billing_note'] = "Activity Notes";

        return $mapped_data;
    }

    public function importPatientData($patient_data)
    {
        // Try to match ContactID, or Fname/Lname/DOB
        $findPatient = "SELECT fname, lname, pubpid, pid
            FROM patient_data
            WHERE (fname = ? AND lname = ? AND DOB = ?)
            ORDER BY `date` DESC
            LIMIT 1";

        $result = sqlQuery($findPatient, [
            $patient_data['fname'],
            $patient_data['lname'],
            $patient_data['DOB']
        ]);
        $pid = null;
        if ($result !== false) {
            // We found a patient, so use that
            $pid = $result['pid'];
        } else {
            $patient_name = $patient_data['fname'] . " " . $patient_data['lname'];
            $this->getLogger()->addMessage("No match found for `$patient_name`, creating new patient");
        }

        $return = null;
        if ($pid === null) {
            $return = $this->patientService->insert($patient_data);
        } else {
            $patient_data['pid'] = $pid;
            $return = $this->patientService->databaseUpdate($patient_data);
        }

        return $return;
    }
}
