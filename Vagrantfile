#
# REQUIREMENTS:
#
# - Linux NFS:
#   - For Ubuntu: http://help.ubuntu.ru/wiki/nfs
#   - For Centos7: https://www.howtoforge.com/nfs-server-and-client-on-centos-7
#

# Read .env file:
env = {}
File.read(".env").split("\n").each do |ef|
  env[ef.split("=")[0]] = ef.split("=")[1]
end

_nfs_mount_options = ["rw", "vers=3", "tcp"]
_nfs_linux__nfs_options = ["rw", "no_subtree_check", "all_squash", "async"]

Vagrant.require_version ">= 1.8.0"
Vagrant.configure("2") do |config|
  config.vm.box = "centos/7"
  config.vm.box_version = "1804.02"
  config.vm.network "private_network", ip: "192.168.56.115", auto_config: true

  config.vm.synced_folder ".", "/vagrant", type: "nfs",
    mount_options: _nfs_mount_options,
    linux__nfs_options: _nfs_linux__nfs_options

  config.vm.provider "virtualbox" do |v|
    v.gui = false
    v.name = "enjoin-1x"
    v.memory = 2048
    v.cpus = 2
    v.customize ["modifyvm", :id, "--cpuexecutioncap", "75"]
    v.customize ["modifyvm", :id, "--ioapic", "on"]
  end

  config.vm.provision "shell", inline: "sudo /vagrant/dev/provision/bin/build.sh"
end
