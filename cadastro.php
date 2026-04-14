  <?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include("conexao.php");
$mensagem = "";
// ========================
// FUNÇÃO VALIDAR CPF
// ========================
function validarCPF($cpf) {
   $cpf = preg_replace('/[^0-9]/', '', $cpf);
   if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
       return false;
   }
   for ($t = 9; $t < 11; $t++) {
       for ($d = 0, $c = 0; $c < $t; $c++) {
           $d += $cpf[$c] * (($t + 1) - $c);
       }
       $d = ((10 * $d) % 11) % 10;
       if ($cpf[$c] != $d) {
           return false;
       }
   }
   return true;
}
// ========================
// FUNÇÃO CRIPTOGRAFAR CPF
// ========================
function criptografarCPF($cpf){
   $cpf = preg_replace('/[^0-9]/', '', $cpf);
   $chave = "minha_chave_secreta_123";
   $metodo = "AES-256-CBC";
   $iv = substr(hash('sha256', $chave), 0, 16);
   return openssl_encrypt($cpf, $metodo, $chave, 0, $iv);
}
// ========================
// FUNÇÃO VALIDAR SENHA FORTE
// ========================
function validarSenhaForte($senha){
   if(strlen($senha) < 8) return false;
   if(!preg_match('/[A-Z]/', $senha)) return false;
   if(!preg_match('/[a-z]/', $senha)) return false;
   if(!preg_match('/[0-9]/', $senha)) return false;
   if(!preg_match('/[\W]/', $senha)) return false;
   return true;
}
// ========================
// PROCESSAR FORMULÁRIO
// ========================
if(isset($_POST['cadastrar'])){
   $email_celular = trim($_POST['email_celular']);
   $senha = $_POST['senha'];
   $valido = false;
   $cpf_criptografado = "";
  
   // Validar senha
   if(!validarSenhaForte($senha)){
       $mensagem = "<p style='color:red;'>A senha deve ter no mínimo 8 caracteres, com letra maiúscula, minúscula, número e símbolo.</p>";
   } else {
      
       // Validar Email
       if(filter_var($email_celular, FILTER_VALIDATE_EMAIL)){
           $valido = true;
       }
      
       // Validar Celular
       if(preg_match('/^[0-9]{10,11}$/', $email_celular)){
           $valido = true;
       }
      
       // Validar CPF
       if(validarCPF($email_celular)){
           // Lista de CPFs "existentes" (para teste)
           $cpfs_existentes = [
               '12345678909',
               '11144477735',
               '98765432100'
           ];
          
           if(!in_array($email_celular, $cpfs_existentes)){
               $mensagem = "<p style='color:red;'>CPF não existe. Digite um CPF válido!</p>";
               $valido = false;
           } else {
               $valido = true;
               $cpf_criptografado = criptografarCPF($email_celular);
           }
       }
      
       if(!$valido){
           if($mensagem == ""){
               $mensagem = "<p style='color:red;'>Digite um Email, Celular ou CPF válido!</p>";
           }
       } else {
           $valor_salvar = $cpf_criptografado ? $cpf_criptografado : $email_celular;
          
           // Verificar duplicado
           $verifica = mysqli_prepare($conexao, "SELECT email_celular FROM usuarios WHERE email_celular = ?");
           if(!$verifica){
               die("Erro no SELECT: " . mysqli_error($conexao));
           }
           mysqli_stmt_bind_param($verifica, "s", $valor_salvar);
           mysqli_stmt_execute($verifica);
           mysqli_stmt_store_result($verifica);
          
           if(mysqli_stmt_num_rows($verifica) > 0){
               $mensagem = "<p style='color:red;'>Usuário já cadastrado!</p>";
           } else {
               $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
              
               $stmt = mysqli_prepare($conexao, "INSERT INTO usuarios (email_celular, senha) VALUES (?, ?)");
               if(!$stmt){
                   die("Erro no INSERT: " . mysqli_error($conexao));
               }
              
               mysqli_stmt_bind_param($stmt, "ss", $valor_salvar, $senha_hash);
              
               if(mysqli_stmt_execute($stmt)){
                   $mensagem = "<p style='color:green;'>Cadastro realizado com sucesso!</p>";
               } else {
                   $mensagem = "<p style='color:red;'>Erro: " . mysqli_error($conexao) . "</p>";
               }
              
               mysqli_stmt_close($stmt);
           }
          
           mysqli_stmt_close($verifica);
       }
   }
}
?>
<?php echo $mensagem; ?>
<form method="POST">
   <label>Email, Celular ou CPF:</label><br>
   <input type="text" name="email_celular" required><br><br>
   <label>Senha (mínimo 8 caracteres, maiúscula, minúscula, número e símbolo):</label><br>
   <input type="password" name="senha" required><br><br>
   <button type="submit" name="cadastrar">Cadastrar</button>
</form>

