<div style='padding: 20px;'>
    <p>este es un test para javascript</p>
    <button id='myButton' style='padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px;'>Haz clic aquí</button>
</div>

<script>
    document.getElementById('myButton').addEventListener('click', function() {
        alert('¡Botón presionado!');
    });
</script>