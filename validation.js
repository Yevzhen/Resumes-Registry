
function doValidate() {
    console.log('Validating...');
    try {
        let pw = document.getElementById('id_1723').value;
		let email = document.getElementById('id_email').value;
        console.log("Validating pw="+pw);
		console.log("Validating email=" + email);
        if (pw == null || pw == "" || email == null || email == "") {
            alert("Both fields must be filled out");
            return false;
        }
        return true;
    } catch(e) {
		console.error("Validation error: " + e);
        return false;
    }
    return false;
}
