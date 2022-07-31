# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|
  config.vm.box = "phz/phz-vagrant-basebox-bionic64"
#  config.vm.provision "shell", path: "scripts/vhosts.sh"
  config.vm.provision "shell", path: "scripts/apt-wait.sh"
#  config.vm.provision "shell", path: "scripts/common-setup.sh"

  config.vm.provider "virtualbox" do |vb|
    vb.memory = "2048"
    vb.cpus = 4
  end

  config.vm.define "backend" do |backend|
    backend.vm.synced_folder ".", "/vagrant"

    backend.vm.network :private_network, ip: "192.168.59.33"
    # config.vm.network :forwarded_port, guest: 80, host: 4567
  end

end
