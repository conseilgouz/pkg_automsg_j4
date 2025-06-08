/**
 * @package AutoMsg
 * @license https://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 * @copyright (c) 2025 ConseilGouz. All Rights Reserved.
 * @author ConseilGouz 
 * 
 **/
document.addEventListener('DOMContentLoaded', function() {
	let btn_ok = document.getElementById("automsg_register_btn");
	let email_ed = document.getElementById("automsg_register_email");
    let res = document.getElementById('automsg_register_msg');
    email_ed.addEventListener('input',function() {
        res.style.display = "none";
        res.innerHTML = "";
    })
	btn_ok.addEventListener('click',function() {
        btn_ok.style.display = "none"; // hide button
      	let csrf = Joomla.getOptions("csrf.token", "");
        let email = email_ed.value.trim();
        let id = document.getElementById('automsg_register_id').value;
        let timestp = document.getElementById('timestp').value;
        if (!email) {// empty
            btn_ok.style.display = "inline-block";
            return;
        }
        if (email.indexOf('@') <= 0) {
            btn_ok.style.display = "inline-block";
            return; 
        }
        url = '?option=com_ajax&module=automsg_register&email='+ email+'&id='+id+'&format=json&timestp='+timestp+'&'+csrf+'=1';
        Joomla.request({
            method : 'POST',
            url : url,
            onSuccess: function(data, xhr) {
                let result = JSON.parse(data);
                msg = result.data;
                res.style.display = "block"
                if (msg.data.error) {
                    res.innerHTML = msg.data.error;
                } else if (msg.data.success){
                    res.innerHTML = msg.data.success;
                } else {
                    res.innerHTML = "invalid message";
                }
                document.getElementById('timestp').value = msg.data.timestp;
                btn_ok.style.display = "inline-block";
            },
            onError: function(message) {
                console.log(message.responseText);
                btn_ok.style.display = "inline-block";
                }
        })
	});
});


