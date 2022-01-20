$(document).ready(function() {
    $('td.password').click(function() {
        var el = document.createElement('textarea');
        el.value = this.innerHTML;
        el.setAttribute('readonly', '');
        el.style = {position: 'absolute', left: '-9999px'};
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        alert("Mot de passe copier");
    });
});