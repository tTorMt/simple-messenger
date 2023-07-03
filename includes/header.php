<?php
//To Do Produce header conditionaly to session
class HeaderProducer
{
    public function produce()
    {
?>
        <header>
            <div class="logo">
                <a href="/"><img src="images/logo.png" alt="logo"></a>
            </div>
            <div class="user-info">
                <h4>Привет, User!</h4>
            </div>
            <div class="login">
                <ul>
                    <a href="auth.php">
                        <li>Войти</li>
                    </a>
                    <a href="registr.php">
                        <li>Регистрация</li>
                    </a>
                    <a href="?exit">
                        <li>Выход</li>
                    </a>
                </ul>
            </div>
        </header>
<?php
    }
}
?>