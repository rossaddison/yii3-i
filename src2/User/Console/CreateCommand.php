<?php

declare(strict_types=1);

namespace App\User\Console;

use App\Auth\Form\SignupForm;
use LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use Yiisoft\Rbac\Manager;
use Yiisoft\Yii\Console\ExitCode;

final class CreateCommand extends Command
{
    protected static $defaultName = 'user/create';

    public function __construct(private SignupForm $signupForm, private Manager $manager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Creates a user')
            ->setHelp('This command allows you to create a user')
            ->addArgument('login', InputArgument::REQUIRED, 'Login')
            ->addArgument('password', InputArgument::REQUIRED, 'Password')
            ->addArgument('isAdmin', InputArgument::OPTIONAL, 'Create user as admin');
    }
    
    /**
     * @psalm-suppress InvalidReturnType, InvalidReturnStatement, UndefinedInterfaceMethod
     */
    protected function execute(InputInterface $input, OutputInterface $output) 
    {
        $io = new SymfonyStyle($input, $output);

        $login = $input->getArgument('login');
        $password = $input->getArgument('password');
        $isAdmin = (bool) $input->getArgument('isAdmin');

        $this->signupForm->load([
            'login' => $login,
            'password' => $password,
            'passwordVerify' => $password,
        ], '');

        try {
            $user = $this->signupForm->signup();
        } catch (Throwable $t) {
            $io->error($t->getMessage() . ' ' . $t->getFile() . ' ' . $t->getLine());

            return $t->getCode() ?: ExitCode::UNSPECIFIED_ERROR;
        }

        if ($user === false) {
            $errors = $this->signupForm->getFormErrors()->getFirstErrors();
            array_walk($errors, fn (string $error, string $attribute) : mixed => $io->error("$attribute: $error"));

            return ExitCode::DATAERR;
        }

        if ($isAdmin) {
            $userId = $user->getId();
            if ($userId === null) {
                throw new LogicException('User Id is NULL');
            }
            $this->manager->assign('admin', $userId);
        }
        $io->success('User created');

        return ExitCode::OK;
    }
}
