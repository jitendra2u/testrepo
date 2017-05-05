<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "myblog";

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);
// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
$id = isset ($_POST['id']) ? $_POST['id'] : "";
$chboxids = isset ($_POST['chboxids']) ? $_POST['chboxids'] : "";

if(($id>0) && ($chboxids!='')){
    $sql="update ckboxupdate set chboxids='".$chboxids."' where id=".$id;
    //$qry=mysqli_query($conn,"update ckboxupdate set chboxids=$chboxids where id=$id");
if (mysqli_query($conn, $sql)) {
    echo "Record updated successfully";
} else {
    echo "Error updating record: " . mysqli_error($conn);
}
  
}






?>
<form name='ckbox' method="post" action="">
    <input type="hidden" name="id" id="id" value="2">
    <input type="hidden" name="chboxids" id="chboxids"> 
    <div class="col-sm-6">
      <input type="checkbox" class="checkboxClass" value="1" name="B2C GizmoSmart" />B2C GizmoSmart
      <input type="checkbox" class="checkboxClass" value="155" name="Business B2B" />Business B2B
      <input type="checkbox" class="checkboxClass" value="152" name="Vodafone India" />Vodafone India
      <input type="checkbox" class="checkboxClass" value="161" name="Sudha P)vt. Ltd." />Sudha P)vt. Ltd.
      <input type="checkbox" class="checkboxClass" value="162" name="Kochartech" />Kochartech
      <input type="checkbox" class="checkboxClass" value="14" name="JITINFO" />JITINFO
    </div>
    <button type="submit" name="submit" class="btn btn-info add-btn-agent col-sm-2">Add Agent</button>
</form>




	


<script src="http://gizmosmart.io/iot/gizmolife_business_admin/asset/js/jquery.min.js"></script>
<script>
$(function(){
//alert('here');
  jQuery(".checkboxClass").click(function(){
        var selectedBusiness = new Array();
        var n = jQuery(".checkboxClass:checked").length;
        if (n > 0){
            jQuery(".checkboxClass:checked").each(function(){
                selectedBusiness.push($(this).val());
            });
        }
       $("#chboxids").val(selectedBusiness);
    });
});


//Selected Business IDs.


</script>
