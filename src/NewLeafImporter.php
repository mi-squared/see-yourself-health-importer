<?php


namespace Mi2\NewLeafImport;

use Mi2\Import\ImporterServiceInterface;
use Mi2\Import\Models\Response;

class NewLeafImporter implements ImporterServiceInterface
{
    protected $count = 0;
    protected $columns = [];

    /**
     * We only support CSV
     * 
     * @param $extension
     * @return bool
     */
    public function supports($extension)
    {
        if ($extension == 'csv') {
            return true;
        }

        return false;
    }

    public function importPatient(array $row)
    {
        // The first row has columns, so set the columns array if we're on row 1
        if ($this->count === 0) {
            //combine the arrays using array_combine
            $this->columns = $row;
            $response = new Response();
        } else {
            $insert_base = array_combine($this->columns, $row);
            $patient_data = $this->buildPatientDataTable($insert_base);
            $response = $this->importPatientData($patient_data);
        }

        $this->count++;

        return $response;
    }

    //This returns the demographic information for a single patient.
    protected function buildPatientDataTable($array)
    {
        $patient_data = array();

        foreach ($array as $key => $val) {

            if (trim(strtolower($key)) == 'first name') {
                $patient_data['fname'] = $val;

            } else if ((trim(strtolower($key)) == 'last name')) {
                $patient_data['lname'] = $val;

            } else if ((trim(strtolower($key)) == 'activity notes')) {
                $patient_data['billing_note'] = $val;

            } else if ((trim(strtolower($key)) == 'dob')) {
                $patient_data['dob'] = date("Y-m-d", strtotime($val));

            } else if ((trim(strtolower($key)) == 'county')) {
                $patient_data['county'] = $val;

            } else if ((trim(strtolower($key)) == 'address line 1')) {
                $patient_data['street'] = $val;

            } else if ((trim(strtolower($key)) == 'address line 2')) {
                //handle the city and state
                $city = explode(' ', $val);
                $patient_data['state'] = array_pop($city);
                $city = implode(" ", $city);
                $patient_data['city'] = $city;

            } else if ((trim(strtolower($key)) == 'address line 3')) {
                $patient_data['postal_code'] = $val;

            } else if ((trim(strtolower($key)) == 'primary phone')) {
                $patient_data['phone_home'] = $val;

            } else if ((trim(strtolower($key)) == 'email address')) {
                $patient_data['email'] = $val;

            } else if ((trim(strtolower($key)) == strtolower('Parent / Guardian Name (Relationship)'))) {
                $guardian_array = explode(' ', $val);
                $patient_data['guardianrelationship'] = array_pop($guardian_array);
                $name = implode(" ", $guardian_array);
                $patient_data['guardiansname'] = $name;

            } else if (strpos($key, 'Gender') !== false) {
                if ($val == "M")
                    $patient_data['sex'] = "Male";
                else if ($val == "F")
                    $patient_data['sex'] = "Female";
                else $patient_data['sex'] = '';

            } else if ((strpos(trim(strtolower($key)), 'ethnicity')) !== false) {

                switch ($val) {
                    case "H":
                        $patient_data['ethnicity'] = 'hisp_or_latin';
                        break;
                    case "B" or "W":
                        $patient_data['ethnicity'] = 'not_hisp_or_latin';
                    default:
                        $patient_data['ethnicity'] = '';
                }
            }
        }

        return $patient_data;
    }

    protected function importPatientData($patient_data){

        //check if the lname, fname, dob exists in the patient_data table
        $sql = "Select * from patient_data where lname = ? and fname = ? and dob = ? ";
        $res = sqlStatement($sql, array($patient_data['lname'], $patient_data['fname'], $patient_data['dob']));
        $numRows = sqlNumRows($res);

        $keys = implode(', ', array_keys($patient_data));
        $values = "'" . implode("','", array_values($patient_data)) . "'";
        $binding = '';
        $update = "Update patient_data set " ;
        foreach($patient_data as $index => $value ) {

            //Here we skip the insurance array and ignore it for the ptData array

            $binding .= "?, ";
            $update .= " $index = ?,";
        }


        // if a record exists we will update, else we will insert
        if($numRows > 0 ){
            $row = sqlFetchArray($res);
            $update = substr($update, 0, -1);
            $update .= " where pid = {$row['pid']} ";
            $success = sqlStatement($update, array_values($patient_data));
            return array('action' => 'update', 'pid' => $row['pid'], 'lname' => $patient_data['lname'], 'fname' => $patient_data['fname']  );

        }else{
            //this is new so we need to get a new pid
            $pid = "select max(pid) as pid from patient_data";
            $newPid = sqlQuery($pid)['pid'] +  1;
            $patient_data = array_merge($patient_data, array('pid' => $newPid));
            //here we add the pid since this is a new patient
            $keys .= ", pid ";
            $values .= ", " . $newPid;
            $binding .= "? ";
            //$values = explode(',', $values);

            $query = 'INSERT into patient_data  ('.$keys.') values ('.$binding.')';
            $success = sqlStatement($query, array_values($patient_data));

            return array('action' => 'insert', 'pid' => $newPid, 'lname' => $patient_data['lname'], 'fname' => $patient_data['fname']  );
        }


    }
}
