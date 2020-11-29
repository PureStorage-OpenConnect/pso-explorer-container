FROM python:alpine
RUN apk add iputils
RUN python -m pip install requests
COPY ping.py .
USER 1001
CMD ["python3", "ping.py"]

