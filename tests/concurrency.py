"""Two independent PHP processes; requires an explicitly disposable local installation."""
import os
import subprocess
import sys
from pathlib import Path
if os.environ.get('SE_DISPOSABLE_TESTS') != 'yes' or len(sys.argv) != 3:
    sys.exit('Set SE_DISPOSABLE_TESTS=yes; arguments: PHP_BINARY WORDPRESS_PATH')
php, site = sys.argv[1:]
helper = str(Path(__file__).with_name('writer-probe.php'))
owner = subprocess.Popen([php, helper, site, 'hold'], stdin=subprocess.PIPE,
                         stdout=subprocess.PIPE, text=True)
try:
    assert owner.stdout.readline().strip() == 'ACQUIRED', 'Owner failed'
    contender = subprocess.run([php, helper, site], capture_output=True, text=True, timeout=20)
    assert contender.returncode == 0 and contender.stdout.strip() == 'BUSY', contender.stdout + contender.stderr
finally:
    owner.communicate('\n', timeout=20)
next_owner = subprocess.run([php, helper, site], capture_output=True, text=True, timeout=20)
assert next_owner.returncode == 0 and next_owner.stdout.strip() == 'ACQUIRED', next_owner.stdout + next_owner.stderr
print('PASS independent contender rejected; lock available after owner exit')
