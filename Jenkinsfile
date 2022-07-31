def CHANGELOG

//Declarative pipeline example
pipeline {
  agent { label 'vagrant' }

  environment {
    PROJECT_NAME = "apprien-magento"
    BUILD_ENV = [master: 'prod', develop: 'stg'].get(env.GIT_BRANCH.split('/')[1], 'dev')
    VERSION = "${BUILD_ENV}-${currentBuild.number}"
  }

  options {
    //colors
    ansiColor('xterm')
  }

  triggers {
    gitlab(triggerOnPush: true, triggerOnMergeRequest: true, branchFilterType: 'All')
  }

  stages {
    stage("Checkout") {
      steps {
        //prevent Jenkins wrong branch checkout failure
        //see https://stackoverflow.com/questions/44928459/is-it-possible-to-rename-default-declarative-checkout-scm-step
        checkout scm

        //parse CHANGELOG
        script {
          def changeLogSets = currentBuild.rawBuild.changeSets
          CHANGELOG = ""
          for (int i = 0; i < changeLogSets.size(); i++) {
            def entries = changeLogSets[i].items
            for (int j = 0; j < entries.length; j++) {
              def entry = entries[j]
              CHANGELOG = CHANGELOG + "${entry.author}: ${entry.msg}\n"
            }
          }
          //prevent double builds, check if changelog is empty, skip
          if (CHANGELOG && CHANGELOG.trim().length() == 0) {
            currentBuild.result = 'SUCCESS'
            return
          }
        }
        echo "Changelog:\n${CHANGELOG}"
      }
    }

    stage("Clean") {
      steps {
        script {
          sh "./down.sh || true"
        }
      }
    }

    stage("Provision") {
      steps {
        timeout(time: 30, unit: 'MINUTES') {
          sh "./up.sh"
        }
      }
    }

    stage("Unit Test") {
      steps {
        //sh "docker-compose run app yarn test-ci"
        junit 'report/junit.xml'
        step([
            $class: 'CloverPublisher',
            cloverReportDir: 'report/coverage',
            cloverReportFileName: 'clover.xml',
            healthyTarget: [methodCoverage: 70, conditionalCoverage: 80, statementCoverage: 80],
            unhealthyTarget: [methodCoverage: 50, conditionalCoverage: 50, statementCoverage: 50],
            failingTarget: [methodCoverage: 0, conditionalCoverage: 0, statementCoverage: 0]
        ])
      }
    }

    stage("Build") {
      steps {
        withCredentials([
          [$class: 'UsernamePasswordMultiBinding', credentialsId: 'DOCKER_REGISTRY', usernameVariable: 'DOCKER_REGISTRY_USERNAME', passwordVariable: 'DOCKER_REGISTRY_PASSWORD']
        ]) {
          script {
            currentBuild.result = hudson.model.Result.SUCCESS.toString()
            if (currentBuild.result=='SUCCESS') {
              sh "./build.sh ${BUILD_ENV} ${VERSION}"
            } else {
              echo "FAIL: Not deploying because currentBuild.result = ${currentBuild.result}"
            }
          }
        }
      }
    }

    stage("Deploy") {
      steps {
        withCredentials([
          [$class: 'UsernamePasswordMultiBinding', credentialsId: 'DOCKER_REGISTRY', usernameVariable: 'DOCKER_REGISTRY_USERNAME', passwordVariable: 'DOCKER_REGISTRY_PASSWORD']
        ]) {
          script {
            currentBuild.result = hudson.model.Result.SUCCESS.toString()
            if (currentBuild.result=='SUCCESS') {
              sh "./deploy.sh ${BUILD_ENV} ${VERSION}", returnStatus:true
            } else {
              echo "FAIL: Not deploying because currentBuild.result = ${currentBuild.result}"
            }
          }
        }
      }
    }
  }

  post {
    always {
      script {
        sh "./down.sh || true"
      }
    }

    success {
      slackSend channel: "#apprien", color: "good", message: "Deployed ${env.JOB_NAME}#${env.BUILD_NUMBER} successfully to ${env.BUILD_ENV}, please Smoke Tests (see README.md #4.1). Add Reaction thumbsup or thumbsdown to indicate Smoke Test cases pass or not.\n${CHANGELOG}"

      emailext (
        subject: "Deployed ${env.JOB_NAME} to ${env.BUILD_ENV} [${env.BUILD_NUMBER}]",
        body: """<p>New build completed and deployed successfully: '${env.JOB_NAME} [${env.BUILD_NUMBER}]':</p>
          <p>Please Smoke Tests (see README.md #4.1). Add Reaction thumbsup or thumbsdown on Slack to indicate Smoke Test cases pass or not.</p>
          <p>Check console output at &QUOT;<a href='${env.BUILD_URL}'>${env.JOB_NAME} [${env.BUILD_NUMBER}]</a>&QUOT;</p>
          <p>${CHANGELOG}""",
        recipientProviders: [[$class: 'DevelopersRecipientProvider']]
      )
      script {
        sh "./down.sh"
      }
    }

    unstable {
      slackSend channel: "#apprien", color: "warning", message: "Unstable build ${env.JOB_NAME}#${env.BUILD_NUMBER} to ${env.BUILD_ENV}, please fix: ${env.BUILD_URL}\n${CHANGELOG}"

      emailext (
        subject: "Unstable build ${env.JOB_NAME} to ${env.BUILD_ENV} [${env.BUILD_NUMBER}]",
        body: """<p>Unstable build: '${env.JOB_NAME} [${env.BUILD_NUMBER}]':</p>
          <p>Check console output at &QUOT;<a href='${env.BUILD_URL}'>${env.JOB_NAME} [${env.BUILD_NUMBER}]</a>&QUOT;</p>
          <p>${CHANGELOG}""",
        recipientProviders: [[$class: 'DevelopersRecipientProvider']]
      )
    }

    failure {
      slackSend channel: "#apprien", color: "danger", message: "FAIL ${env.JOB_NAME}#${env.BUILD_NUMBER} to ${env.BUILD_ENV}, please fix: ${env.BUILD_URL}\n${CHANGELOG}"

      emailext (
        subject: "Failed to build ${env.JOB_NAME} to ${env.BUILD_ENV} [${env.BUILD_NUMBER}]",
        body: """<p>Build failed: '${env.JOB_NAME} [${env.BUILD_NUMBER}]':</p>
          <p>Check console output at &QUOT;<a href='${env.BUILD_URL}'>${env.JOB_NAME} [${env.BUILD_NUMBER}]</a>&QUOT;</p>
          <p>${CHANGELOG}""",
        recipientProviders: [[$class: 'DevelopersRecipientProvider']]
      )
    }
  }
}

