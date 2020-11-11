# Toolkit
Toolkit is a library that provides various helpers and classes to make the development of Liman extensions easier.

Toolkit, Liman eklentilerinin geliştirilmesini kolaylaştırmak için çeşitli yardımcılar ve sınıflar sağlayan bir kütüphanedir.

## Examples / Örnekler
### Distro
```
use Liman\Toolkit\OS\Distro;
```

```
Distro::debian('apt install nano -y')
    ->centos('yum install nano -y')
    ->runSudo();
```

```
Distro::debian("echo 'debian'")
    ->centos("echo 'centos'")
    ->centos6("echo 'centos6'")
    ->centos7("echo 'centos7'")
    ->pardus19("echo 'pardus19'")
    ->pardus192("echo 'pardus19.2'")
    ->pardus193("echo 'pardus19.3'")
    ->ubuntu("echo 'ubuntu'")
    ->ubuntu1804("echo 'ubuntu18.04'")
    ->ubuntu2004("echo 'ubuntu20.04'")
    ->ubuntu2010("echo 'ubuntu20.10'")
    ->default("echo 'Hiçbiri değil'")
    ->run();
```

### Command
```
use Liman\Toolkit\Shell\Command;
```

```
echo Command::run('hostname');
```

```
Command::runSudo('hostnamectl set-hostname @{:hostname}', [
    'hostname' => request('hostname')
]);
```

```
use Liman\Toolkit\Shell\SSHEngine;
```

```
SSHEngine::init(
    request('ipAddress'),
    request('username'),
    request('password')
);
Command::bindEngine(SSHEngine::class);
echo Command::run('hostname');
```

### Formatter
```
use Liman\Toolkit\Formatter;
```

```
echo Formatter::run('hostnamectl set-hostname @{:hostname}', [
    'hostname' => request('hostname')
]);

//output: hostnamectl set-hostname pardus
```

### Validation

[Documantation/Dökümantasyon](https://laravel.com/docs/8.x/validation#available-validation-rules)
```
validate([
    'hostname' => 'required|string'
]);
```
