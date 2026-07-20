<?php
/*
Copyright (C) Innovee Systems Erps. This program should NOT be distributed WITHOUT ANY WARRANTY and LICENSE.
*/

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once("db.php");

// Fallback logic to safely inherit connection from the parent index.php scope
if (!isset($conn) && isset($GLOBALS['conn'])) {
    $conn = $GLOBALS['conn'];
}

$page = $_GET['page'] ?? '';
$part = $_GET['part'] ?? '';

$page = "medicine";
$part = isset($_GET['part']) ? trim($_GET['part']) : '';
$timestamp = date('Y-m-d H:i:s');
$alert = '';
$error = '';
$deleted = 0;
$username = $_SESSION['userfullnames'] ?? 'System';

// Handle Data Submission (Save)
if (isset($_POST['medicine_save'])) {
    $name = ucwords(trim($_POST['name'] ?? ''));
    $molecule_type = ucwords(trim($_POST['molecule_type'] ?? ''));
    $unit_of_measure_id = trim($_POST['unit_of_measure_id'] ?? '');

    if ($name != '' && $molecule_type != '' && $unit_of_measure_id != '') {
        $sql = "INSERT INTO medicine (name, molecule_type, unit_of_measure_id, created_by, created_on)
                VALUES ('" . mysqli_real_escape_string($conn, $name) . "', 
                        '" . mysqli_real_escape_string($conn, $molecule_type) . "', 
                        '" . mysqli_real_escape_string($conn, $unit_of_measure_id) . "', 
                        '" . mysqli_real_escape_string($conn, $username) . "', 
                        '$timestamp')";
        if ($conn->query($sql) === TRUE) {
            $alert = "Data saved successfully!";
            $part = ''; 
        } else {
            $error = "Error saving entry or duplicating records.";
        }
    } else {
        $error = "Please fill in all mandatory fields.";
    }
}

// Handle Data Update
if (isset($_POST['medicine_update'])) {
    $name = ucwords(trim($_POST['name'] ?? ''));
    $molecule_type = ucwords(trim($_POST['molecule_type'] ?? ''));
    $unit_of_measure_id = trim($_POST['unit_of_measure_id'] ?? '');
    $logid = intval($_POST['logid'] ?? 0);

    if ($name != '' && $molecule_type != '' && $unit_of_measure_id != '' && $logid > 0) {
        $sql = "UPDATE medicine SET 
                name = '" . mysqli_real_escape_string($conn, $name) . "', 
                molecule_type = '" . mysqli_real_escape_string($conn, $molecule_type) . "', 
                unit_of_measure_id = '" . mysqli_real_escape_string($conn, $unit_of_measure_id) . "' 
                WHERE logid = '$logid'";
        if ($conn->query($sql) === TRUE) {
            $alert = "Data updated successfully!";
            $part = ''; 
        } else {
            $error = "Error updating database records.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}

// Handle Data Deletion
if (isset($_POST['medicine_delete'])) {
    $count = intval($_POST['txtcount'] ?? 0);
    for ($i = 0; $i < $count; $i++) {
        if (!empty($_POST['del'][$i])) {
            $del_id = mysqli_real_escape_string($conn, $_POST['del'][$i]);
            $sql = "DELETE FROM medicine WHERE logid = '$del_id'";
            if ($conn->query($sql) === TRUE) {
                $deleted++;
            }
        }
    }
    if ($deleted > 0) {
        $alert = "Selected records deleted successfully.";
    } else {
        $error = "No record was selected for deletion.";
    }
}
?>

<!-- Native Style Injection for Clean Tables and Uniform Blue UI Elements -->
<style>
    .chg-container {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        background: #ffffff;
        border-radius: 6px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-top: 10px;
    }

    /* Direct Form Actions Panel Style */
    .chg-actions-panel {
        border: 1px solid #1ea7e1;
        background-color: #f7fcfe;
        border-radius: 4px;
        padding: 12px 15px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .chg-actions-panel-title {
        font-size: 13px;
        font-weight: bold;
        color: #2c3e50;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    /* Header Block Formatting */
    .chg-view-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #eef2f5;
        padding-bottom: 12px;
        margin-bottom: 20px;
    }
    .chg-view-title {
        font-size: 18px;
        color: #2c3e50;
        margin: 0;
        font-weight: 600;
    }

    /* Uniform Blue Custom Buttons System */
    .chg-blue-btn {
        background-color: #1ea7e1 !important;
        color: #ffffff !important;
        padding: 8px 16px;
        font-size: 13px;
        font-weight: bold;
        border-radius: 4px;
        text-decoration: none !important;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: none;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        cursor: pointer;
        transition: background-color 0.2s, box-shadow 0.2s;
    }
    .chg-blue-btn:hover {
        background-color: #1586b7 !important;
        box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        color: #ffffff !important;
    }

    /* Form Fields Styling */
    .chg-form-block {
        max-width: 600px;
        margin-top: 15px;
    }
    .chg-field-group {
        margin-bottom: 15px;
    }
    .chg-field-group label {
        display: block;
        font-weight: bold;
        color: #34495e;
        margin-bottom: 5px;
        font-size: 13px;
    }
    .chg-field-group label span.req {
        color: #e74c3c;
    }
    .chg-input-field {
        width: 100%;
        max-width: 400px;
        padding: 8px 12px;
        border: 1px solid #cccccc;
        border-radius: 4px;
        font-size: 13px;
        box-sizing: border-box;
    }
    .chg-input-field:focus {
        border-color: #1ea7e1;
        outline: none;
    }

    /* Form Bottom Navigation Row */
    .chg-form-actions {
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #eef2f5;
        display: flex;
        gap: 10px;
    }
    .chg-btn-back {
        background-color: #95a5a6;
        color: white;
        padding: 9px 18px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    /* Structured Data Table Layout */
    .chg-data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        border: 1px solid #dddddd;
    }
    .chg-data-table th {
        background-color: #f2f5f8;
        color: #333333;
        font-weight: bold;
        text-align: left;
        padding: 12px 10px;
        border: 1px solid #dddddd;
        font-size: 13px;
    }
    .chg-data-table td {
        padding: 10px;
        border: 1px solid #dddddd;
        font-size: 13px;
        color: #444444;
        background-color: #ffffff;
    }
    .chg-data-table tr:nth-child(even) td {
        background-color: #f9fbfd;
    }
    .chg-data-table tr:hover td {
        background-color: #f1f4f7;
    }
    
    /* Footer controls */
    .chg-table-footer td {
        background-color: #f5f5f5 !important;
        font-weight: bold;
    }
    .chg-delete-btn {
        background-color: #e74c3c;
        color: white;
        border: none;
        padding: 5px 12px;
        border-radius: 3px;
        cursor: pointer;
    }

    /* Alerts */
    .chg-notice { padding: 10px 15px; border-radius: 4px; margin-bottom: 15px; font-size: 13px; }
    .chg-notice-ok { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .chg-notice-err { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>

<!-- Alert/Status Messaging Layer -->
<?php if (!empty($alert)) { echo "<div class='chg-notice chg-notice-ok'><i class='fa fa-check-circle'></i> $alert</div>"; } ?>
<?php if (!empty($error)) { echo "<div class='chg-notice chg-notice-err'><i class='fa fa-exclamation-circle'></i> $error</div>"; } ?>

<!-- Direct Form Actions Panel (Kept strictly on part=1 as requested) -->
<?php if ($part == '1') { ?>
<div class="chg-actions-panel">
    <div class="chg-actions-panel-title">
        <i class="fa fa-cog"></i> Direct Form Actions Panel:
    </div>
    <div style="display: flex; gap: 10px;">
        <button type="button" onclick="document.getElementById('medicineForm').submit();" class="chg-blue-btn">
            <i class="fa fa-save"></i> SAVE NEW ENTRY
        </button>
        <a href="index.php?page=<?= $page ?>" class="chg-blue-btn">
            <i class="fa fa-list"></i> View Records
        </a>
    </div>
</div>
<?php } ?>

<div class="chg-container">
    <div class="chg-view-header">
        <h2 class="chg-view-title">
            Medicine Registry 
            <span style="font-size: 13px; color: #7f8c8d; font-weight: normal; margin-left: 5px;">
                <?php echo ($part=='1') ? '» New Entry Form' : (($part=='2') ? '» Edit Entry Form' : '» Existing Records Matrix'); ?>
            </span>
        </h2>
        
        <div>
            <!-- Create New remains visible here on the records summary table view -->
            <?php if ($part != '1') { ?>
                <a href="index.php?page=<?= $page ?>&part=1" class="chg-blue-btn">
                    <i class="fa fa-plus-circle"></i> Create New
                </a>
            <?php } ?>
        </div>
    </div>

    <form method="POST" action="" id="medicineForm">

    <?php if ($part == '1') { ?>
        <!-- ==================== CREATE LAYOUT ==================== -->
        <div class="chg-form-block">
            <div class="chg-field-group">
                <label for="name">Name : <span class="req">*</span></label>
                <input type="text" id="name" name="name" class="chg-input-field" placeholder="Enter medicine name" required autocomplete="off">
            </div>

            <div class="chg-field-group">
                <label for="molecule_type">Molecule type : <span class="req">*</span></label>
                <input type="text" id="molecule_type" name="molecule_type" class="chg-input-field" placeholder="Enter molecule type" required autocomplete="off">
            </div>

            <div class="chg-field-group">
                <label for="unit_of_measure_id">Unit of measure : <span class="req">*</span></label>
                <select id="unit_of_measure_id" name="unit_of_measure_id" class="chg-input-field" style="height:34px;" required>
                    <option value="">Select Unit of measure</option>
                    <?php
                    $qresult = mysqli_query($conn, "SELECT logid, name FROM unit_of_measure ORDER BY name");
                    while ($drow = mysqli_fetch_assoc($qresult)) {
                        echo "<option value='" . $drow['logid'] . "'>" . htmlspecialchars($drow['name']) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="chg-form-actions">
                <button type="submit" name="medicine_save" class="chg-blue-btn"><i class="fa fa-save"></i> Save Entry</button>
                <a href="index.php?page=<?= $page ?>" class="chg-btn-back"><i class="fa fa-reply"></i> Back to List</a>
            </div>
        </div>

    <?php } elseif ($part == '2') { ?>
        <!-- ==================== EDIT LAYOUT ==================== -->
        <?php 
        $rec = mysqli_real_escape_string($conn, $_GET['rec'] ?? 0);
        $result = mysqli_query($conn, "SELECT * FROM medicine WHERE logid = '$rec'");
        if ($row = mysqli_fetch_assoc($result)) { 
        ?> 
            <input type="hidden" name="logid" value="<?php echo $row['logid']; ?>" />

            <div class="chg-form-block">
                <div class="chg-field-group">
                    <label for="name">Name : <span class="req">*</span></label>
                    <input type="text" id="name" name="name" class="chg-input-field" value="<?php echo htmlspecialchars($row['name']); ?>" required autocomplete="off">
                </div>

                <div class="chg-field-group">
                    <label for="molecule_type">Molecule type : <span class="req">*</span></label>
                    <input type="text" id="molecule_type" name="molecule_type" class="chg-input-field" value="<?php echo htmlspecialchars($row['molecule_type']); ?>" required autocomplete="off">
                </div>

                <div class="chg-field-group">
                    <label for="unit_of_measure_id">Unit of measure : <span class="req">*</span></label>
                    <select id="unit_of_measure_id" name="unit_of_measure_id" class="chg-input-field" style="height:34px;" required>
                        <option value="">Select Unit of measure</option>
                        <?php
                        $qresult = mysqli_query($conn, "SELECT logid, name FROM unit_of_measure ORDER BY name");
                        while ($drow = mysqli_fetch_assoc($qresult)) {
                            $selected = ($drow['logid'] == $row['unit_of_measure_id']) ? "selected" : "";
                            echo "<option value='" . $drow['logid'] . "' $selected>" . htmlspecialchars($drow['name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="chg-form-actions">
                    <button type="submit" name="medicine_update" class="chg-blue-btn"><i class="fa fa-save"></i> Update Entry</button>
                    <a href="index.php?page=<?= $page ?>" class="chg-btn-back"><i class="fa fa-times"></i> Cancel</a>
                </div>
            </div>
        <?php } ?>

    <?php } else { ?>
        <!-- ==================== DATA GRID TABLE LAYOUT ==================== -->
        <div style="overflow-x: auto; width:100%;">
            <table class="chg-data-table">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">#</th>
                        <th>Name</th>
                        <th>Molecule Type</th>
                        <th>Unit Of Measure</th>
                        <th>Created By</th>
                        <th>Created On</th>
                        <th style="width: 70px; text-align: center;">Edit</th> 
                        <th style="width: 70px; text-align: center;">Del</th> 
                    </tr>
                </thead>
                <tbody>
                <?php 
                $mycount = 0;
                $result = mysqli_query($conn, "SELECT * FROM medicine ORDER BY logid DESC");
                
                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) { 
                        $mycount++; 
                ?> 				  
                    <tr>
                        <td style="text-align: center;"><?= $mycount ?></td>
                        <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['molecule_type']); ?></td>
                        <td>
                            <?php
                            $u_id = mysqli_real_escape_string($conn, $row['unit_of_measure_id'] ?? '');
                            $u_res = mysqli_query($conn, "SELECT name FROM unit_of_measure WHERE logid='$u_id'");
                            if ($u_res && $drow = mysqli_fetch_assoc($u_res)) {  
                                echo htmlspecialchars($drow['name']);  
                            } else {
                                echo "<span style='color: #a0a0a0; font-style: italic;'>None</span>";
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['created_by']); ?></td>
                        <td><?php echo htmlspecialchars($row['created_on']); ?></td>
                        <td style="text-align: center;">
                            <a href="index.php?page=<?= $page ?>&part=2&rec=<?php echo $row['logid']; ?>" style="color: #1ea7e1; text-decoration:none;"><i class="fa fa-edit"></i> Edit</a>
                        </td>
                        <td style="text-align: center;">
                            <input name="del[]" type="checkbox" value="<?= $row['logid'] ?>" style="cursor: pointer;" />
                        </td>
                    </tr>
                <?php 
                    } 
                } else { 
                    echo "<tr><td colspan='8' style='text-align: center; color: #7f8c8d; padding: 25px; font-style: italic;'>No medicine records found.</td></tr>";
                } 
                ?>
                </tbody>
                <?php if ($mycount > 0) { ?>
                <tfoot>
                    <tr class="chg-table-footer">
                        <td colspan="7" style="text-align: right; vertical-align: middle; padding-right:15px;">With Selected Items:</td>
                        <td style="text-align: center;">
                            <button name="medicine_delete" type="submit" class="chg-delete-btn" onClick="return confirm('Please confirm deletion of checked records');">
                                <i class="fa fa-trash"></i> Del
                            </button>
                        </td>
                    </tr>
                </tfoot>
                <?php } ?>
            </table>
        </div>
        <input type="hidden" name="txtcount" value="<?php echo $mycount; ?>"> 
    <?php } ?>
    </form>
</div>