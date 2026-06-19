pipeline {

    agent any

    environment {

        DOCKER_SERVER = "10.129.78.111"
        KUBE_SERVER   = "10.129.78.141"

        GIT_URL = "git@10.129.78.171:devbpw/web-stock-opname.git"
        BRANCH  = "main"

        WORKDIR = "/mnt/nas-kube-pcr/project/barcode/fgb-stock-opname"

        REGISTRY = "10.129.78.111:1111"

        NAMESPACE = "fgb-stock-opname"

        IMAGE_TAG = "${BUILD_NUMBER}"
    }

    options {
        timestamps()
    }

    stages {
        stage('Pre Approval') {
            steps {
                script {
                    def approve = input(
                        id: 'PreApproval',
                        message: """
                            PIPELINE TRIGGERED

                            Branch : ${BRANCH}

                            Approve untuk lanjut pipeline?
                        """,
                        parameters: [
                            string(
                                name: 'APPROVE',
                                defaultValue: 'no',
                                description: 'Ketik yes untuk lanjut'
                            )
                        ]
                    )

                    if (approve != 'yes') {
                        error("Pipeline dihentikan")
                    }
                }
            }
        }

        stage('Checkout Source') {
            steps {
                git branch: "${BRANCH}",
                    url: "${GIT_URL}"
            }
        }

        stage('Prepare Repository') {
            steps {
                sh """
                ssh root@${DOCKER_SERVER} '
                    if [ ! -d "${WORKDIR}/.git" ]; then
                        rm -rf ${WORKDIR}
                        git clone ${GIT_URL} ${WORKDIR}
                    else
                        cd ${WORKDIR}
                        git fetch origin
                        git reset --hard origin/${BRANCH}
                    fi
                '
                """
            }
        }

        stage('Build PHP Image') {
            steps {
                sh """
                ssh root@${DOCKER_SERVER} '
                    cd ${WORKDIR}

                    docker build \
                    -f Dockerfile.php \
                    -t ${REGISTRY}/barcode/${NAMESPACE}-php:${IMAGE_TAG} .

                    docker push ${REGISTRY}/barcode/${NAMESPACE}-php:${IMAGE_TAG}
                '
                """
            }
        }

        stage('Build Nginx Image') {
            steps {
                sh """
                ssh root@${DOCKER_SERVER} '
                    cd ${WORKDIR}

                    docker build \
                    -f Dockerfile.nginx \
                    -t ${REGISTRY}/barcode/${NAMESPACE}-nginx:${IMAGE_TAG} .

                    docker push ${REGISTRY}/barcode/${NAMESPACE}-nginx:${IMAGE_TAG}
                '
                """
            }
        }

        stage('Update Deployment Image Tag') {
            steps {
                sh """
                ssh root@${KUBE_SERVER} '
                    cd ${WORKDIR}

                    sed -i "s/IMAGE_TAG/${IMAGE_TAG}/g" deployment.yml
                '
                """
            }
        }

        stage('Deploy Kubernetes') {
            steps {
                sh """
                ssh root@${KUBE_SERVER} '

                    cd ${WORKDIR}

                    kubectl apply -f deployment.yml -n ${NAMESPACE}
                '
                """
            }
        }

        stage('Verify Rollout') {
            steps {
                sh """
                ssh root@${KUBE_SERVER} '

                    kubectl rollout status deployment/laravel-php \
                    -n ${NAMESPACE}

                    kubectl rollout status deployment/laravel-nginx \
                    -n ${NAMESPACE}
                '
                """
            }
        }
    }

    post {

        success {
            echo "DEPLOY SUCCESS"
        }

        failure {

            echo "DEPLOY FAILED"

            sh """
            ssh root@${KUBE_SERVER} '

                kubectl rollout undo deployment/laravel-php \
                -n ${NAMESPACE}

                kubectl rollout undo deployment/laravel-nginx \
                -n ${NAMESPACE}
            '
            """
        }

        always {
            cleanWs()
        }
    }
}