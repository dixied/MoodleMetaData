/**
 * Read uploaded file and manipulate the list.
 */
function test() {
    var x = document.getElementById("id_course_topic");
    var y = document.getElementById("ctopic_file");
    var file_name = y.files[0].name;
    var file_ext = file_name.slice((Math.max(0, file_name.lastIndexOf(".")) || Infinity) + 1);
    if(file_ext == 'csv'){

        var reader = new FileReader();
        reader.onload = function(e){
            var lines = e.target.result.split("\n");
            for(var i = 0; i < lines.length; i++){
                var option = document.createElement("option");
                option.text = lines[i];
                option.value = lines[i];
                x.add(option);
            }
        }   

        reader.readAsText(y.files[0]);
    } else {
        alert("Only .csv file is accepted.");
    }
}


