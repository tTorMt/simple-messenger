<?php

declare(strict_types=1);
//To Do Produce header conditionaly to session
class HeaderProducer
{
    //Header types
    public const HEADER_MAIN = 0;
    public const HEADER_CHAT = 1;
    public const HEADER_AUTH = 2;

    public function __construct(private int $headerType, private $userName = null)
    {
    }

    public function show()
    {
?>
        <header>
            <div class="logo">
                <a href="/"><img src="images/logo.png" alt="logo"></a>
            </div>
            <?php
            switch ($this->headerType) {
            case $this::HEADER_CHAT : {
                if (is_null($this->userName)) {
                    echo "Произошла ошибка. Попробуйте позднее";
                    throw new RuntimeException('Username is null when HEADER_CHAT');
                }
            ?>
                <div class="user-info">
                    <?php
                    echo "<h4>Привет, {$this->userName}!</h4>";
                    ?>
                </div>
                <div class="login">
                        <a href="/?exit">Выход</a>
                </div>
            <?php
            break;
            }
            case $this::HEADER_MAIN : {
            ?>
                <h1>Simple Chat</h1>
                <div class="login">
                    <ul>
                        <a href="auth.php">
                            <li>Войти</li>
                        </a>
                        <a href="registr.php">
                            <li>Регистрация</li>
                        </a>
                    </ul>
                </div>
            <?php break; 
            } 
            case $this::HEADER_AUTH : {
                ?>
                <h1>Авторизация</h1>
                <?php
                break;
            }
        }
            ?>
        </header>
<?php
    }
}
?>